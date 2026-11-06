<?php
/**
 * @filesource modules/product/controllers/pos.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Pos;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Product POS Controller
 *
 * Cashier register: product search, direct paid sale checkout and receipt.
 * Prices and stock are always re-validated server side (see Product\Pos\Model).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Resolve and authorize the module for a POS request.
     *
     * @param object $login
     * @param int    $module_id
     *
     * @return object|null module row or null when unauthorized
     */
    protected function resolveModule($login, $module_id)
    {
        $module = \Index\Module\Model::getModuleWithConfig('product', $module_id);
        if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_pos'])) {
            return null;
        }
        return $module;
    }

    /**
     * POST /api/product/pos/search
     * Search published products/variants for the register.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function search(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $q = $request->post('q')->topic();
            $items = \Product\Pos\Model::search($module->id, $q);

            return $this->successResponse(['items' => $items], 'POS search results');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/pos/checkout
     * Create a paid POS sale. Body: module_id, items (JSON), paid, cust_name.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function checkout(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $items = json_decode($request->post('items')->toJson(), true);
            if (!is_array($items) || empty($items)) {
                return $this->formErrorResponse(['items' => \Kotchasan\Language::get('Cart is empty')], 422);
            }
            $paid = $request->post('paid')->toFloat();
            $custName = $request->post('cust_name')->topic();

            try {
                $result = \Product\Pos\Model::checkout($module, $items, $paid, $custName, $login->id);
            } catch (\RuntimeException $ex) {
                return $this->errorResponse($ex->getMessage(), 422, $ex);
            }

            \Index\Log\Model::add($result['id'], 'product', 'Product', 'POS sale '.$result['order_no'], $login->id);

            return $this->successResponse($result, 'POS sale completed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/product/pos/receipt
     * Receipt payload for a completed sale.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function receipt(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->get('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $order = \Product\Order\Model::getOrderDetail($module->id, $request->get('order_id')->toInt());
            if (!$order) {
                return $this->errorResponse('No data available', 404);
            }

            return $this->successResponse($order, 'Receipt retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
