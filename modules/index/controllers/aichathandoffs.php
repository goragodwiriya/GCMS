<?php
/**
 * @filesource modules/index/controllers/aichathandoffs.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Aichathandoffs;

use Gcms\Api as ApiController;
use Gcms\Chat\HandoffNotifier;
use Gcms\Chat\HandoffStore;
use Gcms\Chat\SettingsRepository;
use Kotchasan\Http\Request;

/**
 * Standard AI handoff table endpoint for admin usage.
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * @var string
     */
    private const TABLE_ID = 'aiChatHandoffs';

    /**
     * @var int
     */
    private const MAX_LIMIT = 200;

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        ApiController::validateMethod($request, 'GET');

        $login = $this->authenticateRequest($request);
        if (!ApiController::hasPermission($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Access denied', 403);
        }

        $page = max(1, $request->get('page')->toInt());
        $pageSize = $request->get('pageSize')->toInt();
        $pageSize = $pageSize > 0 ? min(100, max(10, $pageSize)) : 25;
        $search = trim(str_replace(["\r\n", "\r"], ' ', $request->get('search')->textarea()));
        $sort = trim((string) $request->get('sort'));
        $filters = [
            'status' => strtolower(trim((string) $request->get('status')->filter('a-z'))),
            'channel' => strtolower(trim((string) $request->get('channel')->filter('a-z'))),
            'overdue' => $request->get('overdue')->toInt() === 1
        ];

        $workflow = (new SettingsRepository())->workflow();
        $store = new HandoffStore();
        $rows = array_map(function ($item) {
            return $this->formatRow($item);
        }, $store->latest(self::MAX_LIMIT, $filters, (int) ($workflow['sla_minutes'] ?? 60)));

        if ($search !== '') {
            $rows = array_values(array_filter($rows, function ($row) use ($search) {
                return $this->matchesSearch($row, $search);
            }));
        }

        $rows = $this->sortRows($rows, $sort);
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $page = min($page, $totalPages);
        $offset = max(0, ($page - 1) * $pageSize);

        return $this->successResponse([
            'data' => array_slice($rows, $offset, $pageSize),
            'meta' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => $totalPages
            ]
        ], 'AI handoffs');
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function action(Request $request)
    {
        ApiController::validateMethod($request, 'POST');
        $this->validateCsrfToken($request);

        $login = $this->authenticateRequest($request);
        if (!ApiController::hasPermission($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Access denied', 403);
        }

        $action = $request->post('action')->filter('a-z_');
        $status = $action === 'accepted' ? 'accepted' : ($action === 'closed' ? 'closed' : '');
        $id = $request->post('id')->toInt();
        if ($id < 1 || $status === '') {
            return $this->errorResponse('Invalid handoff request', 422);
        }

        $store = new HandoffStore();
        try {
            $item = $store->updateStatus($id, $status, $login);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        if ($item === null) {
            return $this->errorResponse('Handoff request not found', 404);
        }

        $requesterNotifications = isset($item['requester_notifications']) && is_array($item['requester_notifications']) ? $item['requester_notifications'] : [];
        $requesterNotifications[$status] = (new HandoffNotifier())->notifyRequesterStatus($item, $status);
        $stored = $store->updateRequesterNotifications($id, $requesterNotifications);
        if (is_array($stored)) {
            $item = $stored;
        }

        \Index\Log\Model::add($id, 'index', 'AI Chat', 'Updated AI handoff #'.$id.' to '.$status, (int) ($login->id ?? 0));

        return $this->successResponse([
            'data' => (object) $this->formatRow($item),
            'actions' => [
                [
                    'type' => 'notification',
                    'level' => 'success',
                    'message' => 'Handoff updated'
                ],
                [
                    'type' => 'redirect',
                    'url' => 'reload',
                    'target' => 'table',
                    'tableId' => self::TABLE_ID
                ]
            ]
        ], 'AI handoff updated');
    }

    /**
     * @param array $item
     *
     * @return array
     */
    private function formatRow(array $item): array
    {
        $status = strtolower(trim((string) ($item['status'] ?? 'open')));

        return [
            'id' => (int) ($item['id'] ?? 0),
            'created_at' => trim((string) ($item['created_at'] ?? '')),
            'channel' => strtolower(trim((string) ($item['channel'] ?? 'web'))),
            'channel_display' => $this->channelLabel($item['channel'] ?? 'web'),
            'conversation_id' => trim((string) ($item['conversation_id'] ?? '')),
            'message' => trim((string) ($item['message'] ?? '')),
            'requester_display' => $this->requesterText($item),
            'status' => $status,
            'status_display' => $this->statusLabel($status),
            'age_minutes' => (int) ($item['age_minutes'] ?? 0),
            'age_display' => trim((string) ($item['age_text'] ?? '')).(!empty($item['is_overdue']) ? ' / Over SLA' : ''),
            'details_text' => $this->detailsText($item),
            'can_accept' => $status === 'open',
            'can_close' => $status !== 'closed'
        ];
    }

    /**
     * @param array  $row
     * @param string $search
     *
     * @return bool
     */
    private function matchesSearch(array $row, string $search): bool
    {
        $needle = mb_strtolower(trim($search));
        if ($needle === '') {
            return true;
        }

        foreach (['id', 'created_at', 'channel_display', 'requester_display', 'status_display', 'message', 'conversation_id', 'details_text'] as $field) {
            if (mb_strpos(mb_strtolower((string) ($row[$field] ?? '')), $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array  $rows
     * @param string $sort
     *
     * @return array
     */
    private function sortRows(array $rows, string $sort): array
    {
        $pairs = array_filter(array_map('trim', explode(',', $sort)));
        if (empty($pairs)) {
            return $rows;
        }

        $first = explode(' ', reset($pairs), 2);
        $field = trim((string) ($first[0] ?? ''));
        $direction = strtolower(trim((string) ($first[1] ?? 'asc')));
        $fieldMap = [
            'id' => 'id',
            'created_at' => 'created_at',
            'channel' => 'channel_display',
            'requester_display' => 'requester_display',
            'status' => 'status',
            'age_minutes' => 'age_minutes'
        ];

        if (!isset($fieldMap[$field])) {
            return $rows;
        }

        $sortField = $fieldMap[$field];
        usort($rows, function ($left, $right) use ($sortField, $direction) {
            $leftValue = $left[$sortField] ?? '';
            $rightValue = $right[$sortField] ?? '';

            if (is_numeric($leftValue) && is_numeric($rightValue)) {
                $result = (int) $leftValue <=> (int) $rightValue;
            } else {
                $result = strcasecmp((string) $leftValue, (string) $rightValue);
            }

            return $direction === 'desc' ? -$result : $result;
        });

        return $rows;
    }

    /**
     * @param mixed $channel
     *
     * @return string
     */
    private function channelLabel($channel): string
    {
        switch (strtolower(trim((string) $channel))) {
        case 'line':
            return 'LINE';

        case 'telegram':
            return 'Telegram';

        default:
            return 'Web';
        }
    }

    /**
     * @param string $status
     *
     * @return string
     */
    private function statusLabel(string $status): string
    {
        switch ($status) {
        case 'accepted':
            return 'Accepted';

        case 'closed':
            return 'Closed';

        default:
            return 'Open';
        }
    }

    /**
     * @param array $item
     *
     * @return string
     */
    private function requesterText(array $item): string
    {
        $user = isset($item['user']) && is_array($item['user']) ? $item['user'] : [];
        $source = isset($item['source']) && is_array($item['source']) ? $item['source'] : [];

        foreach (['name', 'email', 'phone', 'username'] as $field) {
            if (!empty($user[$field]) && is_string($user[$field])) {
                return trim($user[$field]);
            }
        }

        if (($item['channel'] ?? '') === 'line') {
            foreach (['userId', 'groupId', 'roomId'] as $field) {
                if (!empty($source[$field]) && is_string($source[$field])) {
                    return trim($source[$field]);
                }
            }
        }

        if (($item['channel'] ?? '') === 'telegram') {
            if (!empty($source['username']) && is_string($source['username'])) {
                return '@'.trim($source['username']);
            }
            foreach (['title', 'id'] as $field) {
                if (!empty($source[$field])) {
                    return trim((string) $source[$field]);
                }
            }
        }

        return 'Guest';
    }

    /**
     * @param array $item
     *
     * @return string
     */
    private function detailsText(array $item): string
    {
        $lines = [];
        $message = trim((string) ($item['message'] ?? ''));
        if ($message !== '') {
            $lines[] = $message;
        }
        $conversationId = trim((string) ($item['conversation_id'] ?? ''));
        if ($conversationId !== '') {
            $lines[] = 'Conversation: '.$conversationId;
        }
        if (!empty($item['accepted_at'])) {
            $acceptedBy = isset($item['accepted_by']['name']) ? trim((string) $item['accepted_by']['name']) : '';
            $lines[] = 'Accepted: '.trim((string) $item['accepted_at']).($acceptedBy !== '' ? ' by '.$acceptedBy : '');
        }
        if (!empty($item['closed_at'])) {
            $closedBy = isset($item['closed_by']['name']) ? trim((string) $item['closed_by']['name']) : '';
            $lines[] = 'Closed: '.trim((string) $item['closed_at']).($closedBy !== '' ? ' by '.$closedBy : '');
        }

        return implode("\n", $lines);
    }
}