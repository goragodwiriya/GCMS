<?php
/**
 * @filesource modules/portfolio/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Settings;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Portfolio Settings Controller — mirrors
 * Product\Settings\Controller's shape. rows/cols control the public
 * listing page's grid (rows * cols = items per page), same as the old
 * gcms241021 Portfolio\Admin\Settings\Model::defaultSettings().
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
            'rows' => 4,
            'cols' => 2,
            'can_write' => [1],
            'can_config' => [1]
        ];
    }

    /**
     * GET /api/portfolio/settings/get
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login || !ApiController::isAdmin($login)) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('portfolio', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $config = $module->config;
            $userStatus = \Gcms\Controller::getUserStatusOptions();
            $configurable = array_values(array_filter($userStatus, static function ($item) {
                return isset($item['value']) && (int) $item['value'] > 1;
            }));
            $response = [
                'module_id' => $module->id,
                'rows' => isset($config->rows) ? (int) $config->rows : 4,
                'cols' => isset($config->cols) ? (int) $config->cols : 2,
                'options' => [
                    'user_status' => $userStatus,
                    'user_status_configurable' => $configurable
                ]
            ];
            foreach (self::$permissionKeys as $key) {
                $response[$key] = $config->$key ?? [1];
            }

            return $this->successResponse($response, 'Portfolio settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/portfolio/settings/save
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

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::canModify($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $module = \Index\Module\Model::getModuleWithConfig('portfolio', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = $module->config;
            $config->rows = min(14, max(1, $request->post('rows')->toInt()));
            $config->cols = min(6, max(1, $request->post('cols')->toInt()));

            foreach (self::$permissionKeys as $key) {
                $statuses = [1];
                $posted = json_decode($request->post($key)->toJson(), true);
                if (is_array($posted)) {
                    foreach ($posted as $status) {
                        $s = (int) $status;
                        if ($s > 1 && !in_array($s, $statuses, true)) {
                            $statuses[] = $s;
                        }
                    }
                }
                $config->$key = $statuses;
            }

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                \Index\Log\Model::add(0, 'portfolio', 'Portfolio', 'Save Portfolio Settings', $login->id);

                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }

        return $this->errorResponse('Failed to save settings', 500);
    }
}
