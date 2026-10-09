<?php
/**
 * @filesource modules/product/controllers/myorders.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Myorders;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Storefront "my orders" API (logged-in members).
 *
 * GET /api/product/myorders          -> { orders, order_statuses, pagination }
 * GET /api/product/myorders/view?id= -> order detail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/product/myorders
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            if (!$login || !isset($login->id)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $ctx = \Product\Lists\Controller::resolveModule($request);
            if (!$ctx) {
                return $this->successResponse(['orders' => [], 'order_statuses' => [], 'pagination' => ['page' => 1, 'pages' => 1, 'page_numbers' => [1]]], 'No product module');
            }

            $status = $request->get('status')->toInt();
            $page = max(1, $request->get('page')->toInt());

            $data = \Product\Order\Model::getMemberOrders($ctx->module_id, (int) $login->id, $status, $page);
            $data['status'] = (string) $status;

            return $this->successResponse($data, 'Orders retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/product/myorders/view?id=
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function view(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            if (!$login || !isset($login->id)) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $ctx = \Product\Lists\Controller::resolveModule($request);
            if (!$ctx) {
                return $this->errorResponse('No product module', 404);
            }

            $order = \Product\Order\Model::getOrderDetail($ctx->module_id, $request->get('id')->toInt(), (int) $login->id);
            if (!$order) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse($order, 'Order retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
