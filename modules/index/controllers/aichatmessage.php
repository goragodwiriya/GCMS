<?php
/**
 * @filesource modules/index/controllers/aichatmessage.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Aichatmessage;

use Gcms\Api as ApiController;
use Gcms\Chat\AiChatAdminContent;
use Gcms\Chat\SettingsRepository;
use Kotchasan\Http\Request;

/**
 * AI chat message-template form actions.
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
        $messages = $repository->messages();
        $key = $request->post('key')->filter('a-z_');
        $row = AiChatAdminContent::messageRow($key, $messages);
        if ($row === null) {
            return $this->formErrorResponse(['value' => 'Invalid message template'], 400);
        }

        $value = trim(str_replace(["\r\n", "\r"], "\n", $request->post('value')->textarea()));
        $messages[$key] = $value;
        if (!$repository->saveMessages($messages)) {
            return $this->errorResponse('Unable to save AI chat message template', 500);
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
                    'tableId' => 'aiChatMessages'
                ],
                [
                    'type' => 'modal',
                    'action' => 'close'
                ]
            ]
        ], 'Saved successfully');
    }
}