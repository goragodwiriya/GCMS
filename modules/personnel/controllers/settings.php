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
     * Shapes a personnel photo can be cropped to: the value of data-crop-aspect
     * (width/height) => that ratio. '' keeps the photo's own shape.
     *
     * @var array
     */
    public static $cropAspects = ['' => 0, '1' => 1, '3/4' => 0.75, '2/3' => 2 / 3, '4/3' => 4 / 3, '16/9' => 16 / 9];

    /**
     * `modules`.`config` for a new personnel instance (see
     * Index\Page\Model::defaultConfig()).
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return [
            'can_manage' => [1],
            'crop_aspect' => '1',
            'crop_width' => 600
        ];
    }

    /**
     * How photos of one personnel module are cropped.
     *
     * The upload form crops in the browser (data-crop) and the server crops the
     * received file again to the same size, so a photo sent without the
     * browser's crop still comes out in the module's shape.
     * A module saved before these settings existed crops square, and a width
     * left empty is 600 px.
     *
     * @param object $config `modules`.`config`
     *
     * @return array ['aspect' => '3/4' (or '' = not cropped), 'width' => px, 'height' => px (0 when not cropped)]
     */
    public static function photoCrop($config)
    {
        $aspect = isset($config->crop_aspect) ? (string) $config->crop_aspect : '1';
        if (!isset(self::$cropAspects[$aspect])) {
            $aspect = '1';
        }
        $width = isset($config->crop_width) ? (int) $config->crop_width : 0;
        $width = $width > 0 ? max(100, min(2000, $width)) : 600;

        return [
            'aspect' => $aspect,
            'width' => $width,
            'height' => $aspect === '' ? 0 : (int) round($width / self::$cropAspects[$aspect])
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
        $crop = self::photoCrop($module->config);
        $response = [
            'module_id' => $module->id,
            'can_manage' => $module->config->can_manage ?? [1],
            'crop_aspect' => $crop['aspect'],
            'crop_width' => $crop['width'],
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
            // Photo crop: a shape from the list ('' = not cropped), width 100-2000 px
            $config->crop_aspect = $request->post('crop_aspect')->toString();
            $config->crop_width = $request->post('crop_width')->toInt();
            $crop = self::photoCrop($config);
            $config->crop_aspect = $crop['aspect'];
            $config->crop_width = $crop['width'];

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
