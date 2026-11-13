<?php
/**
 * @filesource modules/index/controllers/aichatmessages.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Aichatmessages;

use Gcms\Api as ApiController;
use Gcms\Chat\AiChatAdminContent;
use Gcms\Chat\SettingsRepository;
use Kotchasan\Http\Request;

/**
 * AI chat message-template list and row actions.
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

        $rows = AiChatAdminContent::messageRows((new SettingsRepository())->messages());
        $count = count($rows);

        return $this->successResponse([
            'data' => $rows,
            'meta' => [
                'page' => 1,
                'pageSize' => max(1, $count),
                'total' => $count,
                'totalPages' => 1
            ]
        ], 'AI chat message templates');
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

        $action = $request->post('action')->filter('a-z_');
        if ($action !== 'edit') {
            return $this->errorResponse('Invalid action', 400);
        }

        $key = $request->post('id')->filter('a-z_');
        $row = AiChatAdminContent::messageRow($key, (new SettingsRepository())->messages());
        if ($row === null) {
            return $this->errorResponse('Message template not found', 404);
        }

        $row['table_id'] = 'aiChatMessages';

        return $this->successResponse([
            'data' => (object) $row,
            'actions' => [
                [
                    'type' => 'modal',
                    'action' => 'show',
                    'template' => 'ai-chat/message.html',
                    'title' => '{LNG_Edit} '.$row['label'],
                    'titleClass' => 'icon-chat'
                ]
            ]
        ], 'AI chat message template loaded');
    }
}