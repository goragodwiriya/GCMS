<?php
/**
 * @filesource modules/index/controllers/aichatworkflowitem.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Aichatworkflowitem;

use Gcms\Api as ApiController;
use Gcms\Chat\AiChatAdminContent;
use Gcms\Chat\SettingsRepository;
use Gcms\Config;
use Kotchasan\Http\Request;

/**
 * AI chat workflow-setting form actions.
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
    public function save(Request $request)
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

        $repository = new SettingsRepository();
        $workflow = $repository->workflow();
        $key = $request->post('key')->filter('a-z_');
        $row = AiChatAdminContent::workflowRow($key, $workflow);
        if ($row === null) {
            return $this->formErrorResponse(['value' => 'Invalid workflow setting'], 400);
        }

        $value = max(1, min(10080, $request->post('value')->toInt()));
        $config = Config::load(ROOT_PATH.'settings/config.php');
        $config->ai_chat_workflow_value = $value;
        if (!Config::save($config, ROOT_PATH.'settings/config.php')) {
            return $this->errorResponse('Unable to save AI chat workflow setting', 500);
        }

        return $this->successResponse([
            'key' => $key,
            'actions' => [
                [
                    'type' => 'notification',
                    'level' => 'success',
                    'message' => 'Saved successfully'
                ],
                [
                    'type' => 'redirect',
                    'url' => 'reload',
                    'target' => 'table',
                    'tableId' => 'aiChatWorkflow'
                ],
                [
                    'type' => 'modal',
                    'action' => 'close'
                ]
            ]
        ], 'Saved successfully');
    }
}