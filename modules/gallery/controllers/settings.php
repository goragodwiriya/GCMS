<?php
/**
 * @filesource modules/gallery/controllers/settings.php
 *
 * Gallery Settings Controller
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * `modules`.`config` for a new gallery instance (see
     * Index\Page\Model::defaultConfig()).
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return [
            'album_view' => 'slideshow',
            'rows' => 3,
            'cols' => 3,
            'can_upload' => [1]
        ];
    }

    /**
     * GET /api/gallery/settings/get
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
        $module = \Index\Module\Model::getModuleWithConfig('gallery', $request->get('module_id')->toInt());
        if (!$module) {
            return $this->redirectResponse('/404', 'No data available', 404);
        }

        // Prepare response data
        $response = [
            'module_id' => $module->id,
            'album_view' => $module->config->album_view ?? 'slideshow',
            'rows' => (int) ($module->config->rows ?? 3),
            'cols' => (int) ($module->config->cols ?? 3),
            'can_upload' => $module->config->can_upload ?? [1],
            'permissions' => \Gallery\Init\Controller::initPermission(),
            'options' => [
                'user_status' => \Gcms\Controller::getUserStatusOptions()
            ]
        ];

        // Return response
        return $this->successResponse($response, 'Gallery settings loaded');
    }

    /**
     * POST /api/gallery/settings/save
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
            $module = \Index\Module\Model::getModuleWithConfig('gallery', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = $module->config;

            // Execute
            $albumView = $request->post('album_view')->filter('a-z');
            $config->album_view = in_array($albumView, ['slideshow', 'grid']) ? $albumView : 'slideshow';
            $config->cols = min(4, max(1, $request->post('cols')->toInt()));
            $config->rows = min(12, max(1, $request->post('rows')->toInt()));
            $config->can_upload = [1];
            $can_upload = json_decode($request->post('can_upload')->toJson(), true);
            foreach ($can_upload as $status) {
                if ($status !== '1') {
                    $config->can_upload[] = (int) $status;
                }
            }

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                // Log
                \Index\Log\Model::add(0, 'gallery', 'Gallery', 'Save Gallery Settings', $login->id);

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
