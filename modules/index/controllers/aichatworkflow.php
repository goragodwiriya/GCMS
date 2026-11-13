<?php
/**
 * @filesource modules/index/controllers/aichatworkflow.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Aichatworkflow;

use Gcms\Api as ApiController;
use Gcms\Chat\AiChatAdminContent;
use Gcms\Chat\SettingsRepository;
use Kotchasan\Http\Request;

/**
 * AI chat workflow-setting list and row actions.
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

        $rows = AiChatAdminContent::workflowRows((new SettingsRepository())->workflow());
        $count = count($rows);

        return $this->successResponse([
            'data' => $rows,
            'meta' => [
                'page' => 1,
                'pageSize' => max(1, $count),
                'total' => $count,
                'totalPages' => 1
            ]
        ], 'AI chat workflow settings');
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
        $row = AiChatAdminContent::workflowRow($key, (new SettingsRepository())->workflow());
        if ($row === null) {
            return $this->errorResponse('Workflow setting not found', 404);
        }

        $row['table_id'] = 'aiChatWorkflow';

        return $this->successResponse([
            'data' => (object) $row,
            'actions' => [
                [
                    'type' => 'modal',
                    'action' => 'show',
                    'template' => 'ai-chat/workflow.html',
                    'title' => 'Edit '.$row['label'],
                    'titleClass' => 'icon-settings'
                ]
            ]
        ], 'AI chat workflow setting loaded');
    }
}