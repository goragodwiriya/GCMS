<?php
/**
 * @filesource modules/index/controllers/chat.php
 *
 * AI Chat Controller
 *
 * Endpoints:
 *   POST /api/index/chat/message       - process a normalized chat message
 *   GET  /api/index/chat/capabilities  - list registered channels and tools
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Chat;

use Gcms\Api as ApiController;
use Gcms\Chat\HandoffNotifier;
use Gcms\Chat\HandoffStore;
use Gcms\Chat\Processor;
use Gcms\Chat\SettingsRepository;
use Kotchasan\Http\Request;

/**
 * Extensible AI chat endpoint.
 *
 * This controller is intentionally small. Channels and tools are delegated to
 * registries so future modules can extend the system without modifying routing.
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * List available channels and tools.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function capabilities(Request $request)
    {
        ApiController::validateMethod($request, 'GET');

        $processor = new Processor();

        return $this->successResponse($processor->capabilities(), 'AI chat capabilities');
    }

    /**
     * Process a chat message through the shared core.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function message(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $body = $this->jsonBody($request);
        $channelName = strtolower(trim((string) ($body['channel'] ?? 'web')));
        $payload = isset($body['payload']) && is_array($body['payload']) ? $body['payload'] : $body;
        $login = $this->authenticateRequest($request);
        if (!empty($payload['metadata']['debug']) && !ApiController::hasPermission($login, ['can_use_ai_chat'])) {
            unset($payload['metadata']['debug']);
        }
        $processor = new Processor();

        try {
            $result = $processor->process($channelName, $payload, $login);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Unsupported channel' ? 400 : 422;

            return $this->errorResponse($e->getMessage(), $status);
        }

        $response = $result['response'];

        return $this->successResponse([
            'channel' => $response->channel,
            'conversation_id' => $response->conversationId,
            'tool' => $response->tool,
            'message' => $response->message,
            'payload' => $result['payload'],
            'cards' => $response->cards,
            'actions' => $response->actions,
            'meta' => $response->metadata
        ], 'AI chat reply');
    }

    /**
     * List latest AI handoff requests for staff.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function handoffs(Request $request)
    {
        ApiController::validateMethod($request, 'GET');

        $login = $this->authenticateRequest($request);
        if (!ApiController::hasPermission($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Access denied', 403);
        }

        $limit = $request->get('limit')->toInt();
        $limit = $limit > 0 ? min(100, $limit) : 50;
        $filters = [
            'status' => strtolower(trim((string) $request->get('status')->filter('a-z'))),
            'channel' => strtolower(trim((string) $request->get('channel')->filter('a-z'))),
            'overdue' => $request->get('overdue')->toInt() === 1
        ];

        $store = new HandoffStore();
        $workflow = (new SettingsRepository())->workflow();
        $items = $store->latest($limit, $filters, $workflow['sla_minutes']);

        return $this->successResponse([
            'items' => $items,
            'summary' => $store->summary($items, $workflow['sla_minutes']),
            'workflow' => $workflow,
            'filters' => $filters
        ], 'AI handoff list');
    }

    /**
     * Update handoff status.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function handoffStatus(Request $request)
    {
        ApiController::validateMethod($request, 'POST');

        $login = $this->authenticateRequest($request);
        if (!ApiController::hasPermission($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Access denied', 403);
        }

        $body = $this->jsonBody($request);
        $id = (int) ($body['id'] ?? 0);
        $status = strtolower(trim((string) ($body['status'] ?? '')));
        if ($id < 1) {
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
            'item' => $item
        ], 'AI handoff updated');
    }

    /**
     * Poll web handoff status by conversation id.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function handoffProgress(Request $request)
    {
        ApiController::validateMethod($request, 'GET');

        $conversationId = trim((string) $request->get('conversation_id'));
        if ($conversationId === '') {
            return $this->errorResponse('conversation_id is required', 422);
        }

        $store = new HandoffStore();
        $item = $store->latestByConversation($conversationId);
        if ($item === null || ($item['channel'] ?? '') !== 'web') {
            return $this->successResponse([
                'item' => null,
                'changed' => false,
                'status_message' => ''
            ], 'AI handoff progress');
        }

        $currentStatus = strtolower(trim((string) $request->get('current_status')->filter('a-z')));
        $changed = $currentStatus !== strtolower(trim((string) ($item['status'] ?? 'open')));
        $notification = $changed && !empty($item['requester_notifications'][$item['status']]) && is_array($item['requester_notifications'][$item['status']])
            ? $item['requester_notifications'][$item['status']]
            : [];

        return $this->successResponse([
            'item' => $item,
            'changed' => $changed,
            'status_message' => trim((string) ($notification['message'] ?? ''))
        ], 'AI handoff progress');
    }

    /**
     * Decode request body from form POST or raw JSON.
     *
     * @param Request $request
     *
     * @return array
     */
    private function jsonBody(Request $request)
    {
        $raw = $request->getParsedBody();
        if (is_array($raw) && !empty($raw)) {
            return $raw;
        }

        $content = (string) $request->getBody();
        if ($content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @param array $items
     *
     * @return array
     */
    private function handoffSummary(array $items)
    {
        $channels = [];
        $statuses = [
            'open' => 0,
            'accepted' => 0,
            'closed' => 0
        ];
        foreach ($items as $item) {
            $channel = strtolower(trim((string) ($item['channel'] ?? 'web')));
            if ($channel === '') {
                $channel = 'web';
            }
            if (!isset($channels[$channel])) {
                $channels[$channel] = 0;
            }
            ++$channels[$channel];

            $status = strtolower(trim((string) ($item['status'] ?? 'open')));
            if (!isset($statuses[$status])) {
                $statuses[$status] = 0;
            }
            ++$statuses[$status];
        }
        ksort($channels);

        return [
            'total' => count($items),
            'channels' => $channels,
            'statuses' => $statuses,
            'latest_at' => isset($items[0]['created_at']) ? (string) $items[0]['created_at'] : ''
        ];
    }
}