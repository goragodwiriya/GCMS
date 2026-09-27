<?php
/**
 * @filesource widgets/personnel/controllers/settings.php
 *
 * Personnel Widget – Settings Controller
 *
 * GET  ../api/index/widgets/get?widget=personnel
 *   → returns module list, level options, and (if module_id is given) departments.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Personnel\Controllers;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Settings controller for the Personnel widget.
 * Called by Index\Widgets\Controller via delegateToWidget().
 */
class Settings extends \Kotchasan\ApiController
{
    /**
     * GET  ../api/index/widgets/get?widget=personnel[&module_id=N]
     *
     * Returns:
     *  - modules    : installed personnel module instances [{value, text, module_id}]
     *  - levels     : static level options [{value, text}]
     *  - departments: categories of type 'department' for the given module_id (empty when omitted)
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

            // Bootstrap language so category translations work correctly
            Language::name();

            // --- Module list ---
            $moduleRows = \Kotchasan\Model::createQuery()
                ->select('id', 'module')
                ->from('modules')
                ->where(['owner', 'personnel'])
                ->orderBy('module', 'ASC')
                ->fetchAll();

            $modules = [];
            foreach ($moduleRows as $row) {
                $modules[] = [
                    'value'     => $row->module,
                    'text'      => $row->module,
                    'module_id' => (int) $row->id,
                ];
            }

            // --- Level options (static) ---
            $levels = \Personnel\Category\Model::levelOptions();

            // --- Departments (only when module_id is provided) ---
            $departments = [];
            $module_id = $request->get('module_id')->toInt();
            if ($module_id > 0) {
                $departments = \Personnel\Category\Model::toOptions($module_id, 'department');
            }

            return $this->successResponse([
                'modules'     => $modules,
                'levels'      => $levels,
                'departments' => $departments,
            ], 'Personnel settings options');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
