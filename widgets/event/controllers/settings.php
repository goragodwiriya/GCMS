<?php
/**
 * @filesource widgets/event/controllers/settings.php
 *
 * Event Widget — Settings Controller
 *
 * GET ../api/index/widgets/get?widget=event → list of installed
 * event-type module instances, used by the Designer to populate the
 * "Module" dropdown instead of a free-text field. Mirrors
 * widgets/gallery/controllers/settings.php.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Event\Controllers;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Settings controller for the Event widget.
 * Called by Index\Widgets\Controller via delegateToWidget().
 */
class Settings extends \Kotchasan\ApiController
{
    /**
     * GET ../api/index/widgets/get?widget=event
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            if (!ApiController::hasPermission($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $rows = \Kotchasan\Model::createQuery()
                ->select('module')
                ->from('modules')
                ->where(['owner', 'event'])
                ->orderBy('module', 'ASC')
                ->fetchAll();

            $modules = [];
            foreach ($rows as $row) {
                $modules[] = ['value' => $row->module, 'label' => $row->module];
            }

            return $this->successResponse(['modules' => $modules], 'Event widget options');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
