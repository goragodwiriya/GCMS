<?php
/**
 * @filesource modules/product/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Settings;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Product Settings Controller
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Permission config keys that map a user status array to a capability.
     *
     * @var array
     */
    protected static $permissionKeys = ['can_manage', 'can_pos', 'can_verify_payment', 'can_manage_stock'];

    /**
     * `modules`.`config` for a new product instance — the same values the
     * settings form (get()) falls back to when a key is missing
     * (Index\Page\Model::defaultConfig() writes this on creation).
     *
     * @return array
     */
    public static function defaultSettings()
    {
        $config = [
            'store_name' => '',
            'currency' => 'THB',
            'bank_info' => '',
            'promptpay_id' => '',
            'cod_enabled' => 1,
            'transfer_enabled' => 1,
            'order_prefix' => ''
        ];
        foreach (self::$permissionKeys as $key) {
            $config[$key] = [1];
        }

        return $config;
    }

    /**
     * GET /api/product/settings/get
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

            $module = \Index\Module\Model::getModuleWithConfig('product', $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $config = $module->config;
            $response = [
                'module_id' => $module->id,
                'store_name' => $config->store_name ?? '',
                'currency' => $config->currency ?? 'THB',
                'bank_info' => $config->bank_info ?? '',
                'promptpay_id' => $config->promptpay_id ?? '',
                'cod_enabled' => isset($config->cod_enabled) ? (int) $config->cod_enabled : 1,
                'transfer_enabled' => isset($config->transfer_enabled) ? (int) $config->transfer_enabled : 1,
                'order_prefix' => $config->order_prefix ?? '',
                'options' => [
                    'user_status' => \Gcms\Controller::getUserStatusOptions(),
                    'currencies' => [
                        ['value' => 'THB', 'text' => 'THB (฿)'],
                        ['value' => 'USD', 'text' => 'USD ($)'],
                        ['value' => 'EUR', 'text' => 'EUR (€)']
                    ]
                ]
            ];
            foreach (self::$permissionKeys as $key) {
                $response[$key] = $config->$key ?? [1];
            }

            return $this->successResponse($response, 'Product settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/settings/save
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

            $module = \Index\Module\Model::getModuleWithConfig('product', $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $config = $module->config;
            $config->store_name = $request->post('store_name')->topic();
            $config->currency = $request->post('currency')->filter('A-Z') ?: 'THB';
            $config->bank_info = $request->post('bank_info')->textarea();
            // store PromptPay: mobile (10 digits), tax id (13) or e-wallet (15)
            $promptpay = preg_replace('/[^0-9]/', '', $request->post('promptpay_id')->toString());
            if ($promptpay !== '' && !in_array(strlen($promptpay), [10, 13, 15], true)) {
                return $this->formErrorResponse(['promptpay_id' => 'Invalid PromptPay ID'], 422);
            }
            $config->promptpay_id = $promptpay;
            $config->cod_enabled = $request->post('cod_enabled')->toBoolean() ? 1 : 0;
            $config->transfer_enabled = $request->post('transfer_enabled')->toBoolean() ? 1 : 0;
            $config->order_prefix = $request->post('order_prefix')->filter('A-Z0-9');

            // Permission status arrays (status 1 / admin always included)
            foreach (self::$permissionKeys as $key) {
                $statuses = [1];
                $posted = json_decode($request->post($key)->toJson(), true);
                if (is_array($posted)) {
                    foreach ($posted as $status) {
                        $s = (int) $status;
                        if ($s !== 1 && !in_array($s, $statuses, true)) {
                            $statuses[] = $s;
                        }
                    }
                }
                $config->$key = $statuses;
            }

            if (\Index\Module\Model::updateConfig($module->id, $config)) {
                \Index\Log\Model::add(0, 'product', 'Product', 'Save Product Settings', $login->id);
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
