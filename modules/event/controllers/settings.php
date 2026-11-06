<?php
/**
 * @filesource modules/event/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Settings;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Event Settings Controller — mirrors Video\Settings\Controller's shape.
 * The event module is calendar-based (no rows/cols); only member roles are
 * configurable, same as the old gcms241021
 * Event\Admin\Settings\Model::defaultSettings().
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
            'can_write' => [1],
            'can_config' => [1]
        ];
    }

    /**
     * GET /api/event/settings/get
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

            $module = \Index\Module\Model::getModuleWithConfig('event', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $config = $module->config;
            $response = [
                'module_id' => $module->id,
                'options' => [
                    'user_status' => \Gcms\Controller::getUserStatusOptions()
                ]
            ];
            foreach (self::$permissionKeys as $key) {
                $response[$key] = $config->$key ?? [1];
            }

            return $this->successResponse($response, 'Event settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/event/settings/save
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

            $module = \Index\Module\Model::getModuleWithConfig('event', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = $module->config;
            $config->can_write = $this->parseStatusList($request, 'can_write', true);
            $config->can_config = $this->parseStatusList($request, 'can_config', true);

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                \Index\Log\Model::add(0, 'event', 'Event', 'Save Event Settings', $login->id);

                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }

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
