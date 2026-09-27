<?php
/**
 * @filesource modules/index/controllers/aichatquickanswers.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Aichatquickanswers;

use Gcms\Api as ApiController;
use Gcms\Chat\QuickAnswerRepository;
use Kotchasan\Http\Request;

/**
 * AI chat quick-answer list and row actions.
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        ApiController::validateMethod($request, 'GET');

        $login = $this->authenticateRequest($request);
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!ApiController::canModify($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Forbidden', 403);
        }

        $rows = (new QuickAnswerRepository())->all(false);
        $count = count($rows);

        return $this->successResponse([
            'data' => $rows,
            'meta' => [
                'page' => 1,
                'pageSize' => max(1, $count),
                'total' => $count,
                'totalPages' => 1
            ]
        ], 'AI chat quick answers');
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
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!ApiController::canModify($login, ['can_use_ai_chat'])) {
            return $this->errorResponse('Forbidden', 403);
        }

        $repository = new QuickAnswerRepository();
        $items = $repository->all(false);
        $action = $request->post('action')->filter('a-z_');

        $id = $request->post('id')->toInt();
        $index = $this->findItemIndex($items, $id);

        if ($action === 'edit') {
            if ($index < 0) {
                return $this->errorResponse('Quick answer not found', 404);
            }

            $row = $items[$index];
            $row['table_id'] = 'aiChatQuickAnswers';

            return $this->successResponse([
                'data' => (object) $row,
                'actions' => [
                    [
                        'type' => 'modal',
                        'action' => 'show',
                        'template' => 'ai-chat/quick-answer.html',
                        'title' => 'Edit Quick Answer',
                        'titleClass' => 'icon-edit'
                    ]
                ]
            ], 'AI chat quick answer loaded');
        }

        if ($action === 'delete') {
            if ($index < 0) {
                return $this->errorResponse('Quick answer not found', 404);
            }

            array_splice($items, $index, 1);
            if (!$repository->saveMany($items)) {
                return $this->errorResponse('Unable to delete quick answer', 500);
            }

            return $this->successResponse([
                'actions' => $this->reloadActions('Deleted successfully')
            ], 'Deleted successfully');
        }

        if ($action === 'published') {
            if ($index < 0) {
                return $this->errorResponse('Quick answer not found', 404);
            }

            $items[$index]['published'] = !empty($items[$index]['published']) ? 0 : 1;
            if (!$repository->saveMany($items)) {
                return $this->errorResponse('Unable to update quick answer status', 500);
            }

            return $this->successResponse([
                'actions' => $this->reloadActions('Saved successfully')
            ], 'Saved successfully');
        }

        return $this->errorResponse('Invalid action', 400);
    }

    /**
     * @param array $items
     * @param int   $id
     *
     * @return int
     */
    private function findItemIndex(array $items, int $id): int
    {
        foreach ($items as $index => $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * @param string $message
     *
     * @return array
     */
    private function reloadActions(string $message): array
    {
        return [
            [
                'type' => 'notification',
                'level' => 'success',
                'message' => $message
            ],
            [
                'type' => 'redirect',
                'url' => 'reload',
                'target' => 'table',
                'tableId' => 'aiChatQuickAnswers'
            ]
        ];
    }
}