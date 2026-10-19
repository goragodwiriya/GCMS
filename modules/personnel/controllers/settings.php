<?php
/**
 * @filesource modules/personnel/controllers/settings.php
 *
 * Personnel Settings Controller
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * `modules`.`config` for a new personnel instance (see
     * Index\Page\Model::defaultConfig()).
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return [
            'can_manage' => [1]
        ];
    }

    /**
     * GET /api/personnel/settings/get
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        // Validate request method (GET request doesn't need CSRF token)
        ApiController::validateMethod($request, 'GET');

        // Authentication check (required)
        $login = $this->authenticateRequest($request);
        if (!$login || !ApiController::isAdmin($login)) {
            return $this->redirectResponse('/login', 'Unauthorized', 401);
        }

        // Load module configuration
        $module = \Index\Module\Model::getModuleWithConfig('personnel', $request->get('module_id')->toInt());
        if (!$module) {
            return $this->redirectResponse('/404', 'No data available', 404);
        }

        // Prepare response data
        $response = [
            'module_id' => $module->id,
            'can_manage' => $module->config->can_manage ?? [1],
            'permissions' => \Gallery\Init\Controller::initPermission(),
            'options' => [
                'user_status' => \Gcms\Controller::getUserStatusOptions()
            ]
        ];

        // Return response
        return $this->successResponse($response, 'Personnel settings loaded');
    }

    /**
     * POST /api/personnel/settings/save
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            ApiController::validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Authorization for saving
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('personnel', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = $module->config;

            // Execute
            $config->can_manage = [1];
            $can_manage = json_decode($request->post('can_manage')->toJson(), true);
            foreach ($can_manage as $status) {
                if ($status !== '1') {
                    $config->can_manage[] = (int) $status;
                }
            }

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                // Log
                \Index\Log\Model::add(0, 'personnel', 'Personnel', 'Save Personnel Settings', $login->id);

                // Reload page
                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }
        } catch (\Kotchasan\ApiException $e) {
            // Keep original HTTP code (e.g. 403 CSRF, 405 method)
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        }
        // Error save settings
        return $this->errorResponse('Failed to save settings', 500);
    }
}
