<?php
/**
 * @filesource modules/board/controllers/action.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Action;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Boards Action Controller (Frontend)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Get custom parameters for table
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            ApiController::validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $action = $request->post('action')->filter('a-z');
            $id = $request->post('id')->toInt();
            $module_id = $request->post('module_id')->toInt();
            $reply_id = $request->post('reply_id')->toInt();

            $module = \Index\Module\Model::getModuleWithConfig('board', $module_id);
            if (!$module || !\Web\Login::checkStatus($login, $module->config, ['moderator'])) {
                return $this->errorResponse('No data available', 404);
            }

            if ($action === 'delete') {
                if ($reply_id === 0) {
                    // Delete topic and all its replies
                    $result = Model::deleteTopic($id, $module->id);
                    // Log message for topic deletion
                    $message = 'Delete topic: '.$id;
                    $id = 0;
                } else {
                    // Delete a specific reply
                    $result = Model::deleteReply($reply_id, $module->id);
                    // Log message for reply deletion
                    $message = 'Delete reply: '.$reply_id;
                }
            } elseif ($action === 'pin' || $action === 'unpin') {
                // Toggle pin status of the topic
                $result = Model::togglePin($id, $module->id, $action);
                // Log message for pin/unpin action
                $message = ucfirst($action).' topic: '.$id;
            } elseif ($action === 'lock' || $action === 'unlock') {
                // Toggle lock status of the topic
                $result = Model::toggleLock($id, $module->id, $action);
                // Log message for lock/unlock action
                $message = ucfirst($action).' topic: '.$id;
            } else {
                return $this->errorResponse('Invalid action', 400);
            }

            if ($result > 0) {
                // Log
                \Index\Log\Model::add($id, 'board', 'Board', $message, $login->id);

                if ($action === 'delete' && $reply_id === 0) {
                    // Return to board
                    $url = \Board\Index\Controller::url($module->module);
                    return $this->redirectResponse($url, $message, 200, 1000);
                }
                // Reload current page
                return $this->redirectResponse('reload', $message, 200, 1000);
            }
        } catch (\Kotchasan\ApiException $e) {
            // Keep original HTTP code (e.g. 403 CSRF, 405 method)
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        }
    }
}
