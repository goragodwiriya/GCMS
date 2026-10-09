<?php
/**
 * @filesource modules/video/controllers/settings.php
 *
 * Video Settings Controller
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Settings;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Video Settings Controller — mirrors Portfolio\Settings\Controller's
 * shape. rows/cols control the public listing page's grid
 * (rows * cols = items per page), google_api_key is used to look up
 * video details from the YouTube API, same as the old gcms241021
 * Video\Admin\Settings\Model::defaultSettings().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * @var array
     */
    protected static $permissionKeys = ['can_write', 'can_config'];

    /**
     * @return array
     */
    public static function defaultSettings()
    {
        return [
            'google_api_key' => '',
            'rows' => 4,
            'cols' => 4,
            'can_write' => [1],
            'can_config' => [1]
        ];
    }

    /**
     * GET /api/video/settings/get
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            // Validate request method (GET request doesn't need CSRF token)
            ApiController::validateMethod($request, 'GET');

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Load module configuration
            $module = \Index\Module\Model::getModuleWithConfig('video', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $config = $module->config;

            $response = [
                'module_id' => $module->id,
                'google_api_key' => $config->google_api_key ?? '',
                'rows' => isset($config->rows) ? (int) $config->rows : 4,
                'cols' => isset($config->cols) ? (int) $config->cols : 4,
                'options' => [
                    'user_status' => \Gcms\Controller::getUserStatusOptions()
                ]
            ];
            foreach (self::$permissionKeys as $key) {
                $response[$key] = $config->$key ?? [1];
            }

            // Return response
            return $this->successResponse($response, 'Video settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/video/settings/save
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
            $module = \Index\Module\Model::getModuleWithConfig('video', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = $module->config;
            $config->google_api_key = $request->post('google_api_key')->topic();
            $config->rows = min(14, max(1, $request->post('rows')->toInt()));
            $config->cols = min(6, max(1, $request->post('cols')->toInt()));
            $config->can_write = $this->parseStatusList($request, 'can_write', true);
            $config->can_config = $this->parseStatusList($request, 'can_config', true);

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                // Log
                \Index\Log\Model::add(0, 'video', 'Video', 'Save Video Settings', $login->id);

                // Reload page
                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }
        } catch (\Kotchasan\ApiException $e) {
            // Keep original HTTP code (e.g. 403 CSRF, 405 method)
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
        // Error save settings
        return $this->errorResponse('Failed to save settings', 500);
    }

    /**
     * Parse JSON array status list and optionally force member(1).
     *
     * @param Request $request
     * @param string  $key
     * @param bool    $includeMember
     *
     * @return array
     */
    private function parseStatusList(Request $request, string $key, bool $includeMember = false): array
    {
        $statuses = $includeMember ? [1] : [];
        $posted = json_decode($request->post($key)->toJson(), true);
        if (is_array($posted)) {
            foreach ($posted as $status) {
                $s = (int) $status;
                if ((!$includeMember || $s !== 1) && !in_array($s, $statuses, true)) {
                    $statuses[] = $s;
                }
            }
        }
        return $statuses;
    }
}
