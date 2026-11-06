<?php
/**
 * @filesource modules/product/controllers/order.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Order;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API Product Order Controller (admin detail + actions)
 *
 * Admin order detail with payment verification, status updates and shipment
 * creation. Stock is restored once when an order moves into the cancelled
 * status (reusing the FIFO engine), and every change is logged.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Resolve and authorize the module for an order request.
     *
     * @param object $login
     * @param int    $module_id
     *
     * @return object|null module row or null when unauthorized
     */
    protected function resolveModule($login, $module_id)
    {
        $module = \Index\Module\Model::getModuleWithConfig('product', $module_id);
        if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage'])) {
            return null;
        }
        return $module;
    }

    /**
     * Load the raw order row (admin scope, no member restriction).
     *
     * @param int $module_id
     * @param int $order_id
     *
     * @return object|null
     */
    protected function loadOrder($module_id, $order_id)
    {
        return \Kotchasan\Model::createQuery()
            ->select()
            ->from('product_order')
            ->where([['id', $order_id], ['module_id', $module_id]])
            ->first();
    }

    /**
     * Order items including variant_id/qty (for stock restore).
     *
     * @param int $order_id
     *
     * @return array
     */
    protected function loadItems($order_id)
    {
        return \Kotchasan\Model::createQuery()
            ->select('id', 'product_id', 'variant_id', 'product_name', 'variant_label', 'sku', 'unit_price', 'qty', 'line_total')
            ->from('product_order_item')
            ->where(['order_id', $order_id])
            ->orderBy('id')
            ->fetchAll();
    }

    /**
     * GET /api/product/order/get
     * Full admin order detail (order + items + payment + history + shipments).
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
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->get('module_id')->toInt());
            if (!$module) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $order_id = $request->get('id')->toInt();
            $order = $this->loadOrder($module->id, $order_id);
            if (!$order) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $items = $this->loadItems($order_id);

            $payment = \Kotchasan\Model::createQuery()
                ->select('id', 'method', 'amount', 'status', 'slip_image', 'bank_account', 'ref', 'paid_at', 'verified_by', 'verified_at')
                ->from('product_payment')
                ->where(['order_id', $order_id])
                ->orderBy('id', 'DESC')
                ->first();

            $history = \Kotchasan\Model::createQuery()
                ->select('id', 'order_status', 'note', 'changed_by', 'created_at')
                ->from('product_order_status_history')
                ->where(['order_id', $order_id])
                ->orderBy('id', 'DESC')
                ->fetchAll();
            foreach ($history as $h) {
                $h->order_status_text = \Product\Order\Model::statusText((int) $h->order_status);
            }

            $shipments = \Kotchasan\Model::createQuery()
                ->select('id', 'shipping_method_id', 'tracking_no', 'status', 'shipped_at', 'note', 'created_at')
                ->from('product_shipment')
                ->where(['order_id', $order_id])
                ->orderBy('id', 'DESC')
                ->fetchAll();

            $proof = '';
            if ($payment && $payment->slip_image !== '') {
                $proof = WEB_URL.DATA_FOLDER.'product/slip/'.$payment->slip_image;
            }

            return $this->successResponse([
                'module_id' => $module->id,
                'order' => [
                    'id' => (int) $order->id,
                    'order_no' => $order->order_no,
                    'channel' => $order->channel,
                    'order_status' => (int) $order->order_status,
                    'order_status_text' => \Product\Order\Model::statusText((int) $order->order_status),
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'payment_date' => $order->payment_date,
                    'created_at' => $order->created_at,
                    'cust_name' => $order->cust_name,
                    'cust_phone' => $order->cust_phone,
                    'cust_email' => $order->cust_email,
                    'ship_address' => $order->ship_address,
                    'ship_province' => $order->ship_province,
                    'ship_zipcode' => $order->ship_zipcode,
                    'subtotal' => (float) $order->subtotal,
                    'shipping_fee' => (float) $order->shipping_fee,
                    'discount' => (float) $order->discount,
                    'grand_total' => (float) $order->grand_total,
                    'note' => $order->note
                ],
                'items' => $items,
                'payment' => $payment,
                'payment_proof' => $proof,
                'history' => $history,
                'shipments' => $shipments,
                'status_options' => \Product\Order\Model::statusOptions(),
                'shipping_options' => \Product\Shipping\Model::options($module->id)
            ], 'Order detail retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/order/verifyPayment
     * Approve or reject the latest payment record.
     * Body: module_id, order_id, decision (approve|reject).
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function verifyPayment(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = \Index\Module\Model::getModuleWithConfig('product', $request->post('module_id')->toInt());
            if (!$module || !\Product\Init\Controller::allowed($login, $module->config, ['can_manage', 'can_verify_payment'])) {
                return $this->errorResponse('No data available', 404);
            }

            $order_id = $request->post('order_id')->toInt();
            $order = $this->loadOrder($module->id, $order_id);
            if (!$order) {
                return $this->errorResponse('No data available', 404);
            }

            $payment = \Kotchasan\Model::createQuery()
                ->select('id')
                ->from('product_payment')
                ->where(['order_id', $order_id])
                ->orderBy('id', 'DESC')
                ->first();
            if (!$payment) {
                return $this->errorResponse('No payment to verify', 404);
            }

            $decision = $request->post('decision')->filter('a-z');
            $db = \Kotchasan\DB::create();
            $now = date('Y-m-d H:i:s');

            if ($decision === 'reject') {
                $db->update('product_payment', ['id', $payment->id], [
                    'status' => 'rejected',
                    'verified_by' => $login->id,
                    'verified_at' => $now
                ]);
                $db->update('product_order', ['id', $order_id], ['payment_status' => 'failed']);
                \Index\Log\Model::add($order_id, 'product', 'Product', 'Reject payment order '.$order->order_no, $login->id);
            } else {
                $db->update('product_payment', ['id', $payment->id], [
                    'status' => 'verified',
                    'verified_by' => $login->id,
                    'verified_at' => $now,
                    'paid_at' => $now
                ]);
                $update = [
                    'payment_status' => 'paid',
                    'payment_date' => $now
                ];
                // Move into "Paid" only when still pending payment.
                if ((int) $order->order_status === 1) {
                    $update['order_status'] = 2;
                    $db->insert('product_order_status_history', [
                        'id' => $db->nextId('product_order_status_history'),
                        'order_id' => $order_id,
                        'module_id' => $module->id,
                        'order_status' => 2,
                        'note' => 'Payment verified',
                        'changed_by' => $login->id,
                        'created_at' => $now
                    ]);
                }
                $db->update('product_order', ['id', $order_id], $update);
                \Index\Log\Model::add($order_id, 'product', 'Product', 'Verify payment order '.$order->order_no, $login->id);
            }

            return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/order/updateStatus
     * Change order status. Moving INTO the cancelled status (6) from a
     * non-cancelled status restores FIFO stock once.
     * Body: module_id, order_id, order_status, note.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function updateStatus(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $order_id = $request->post('order_id')->toInt();
            $order = $this->loadOrder($module->id, $order_id);
            if (!$order) {
                return $this->errorResponse('No data available', 404);
            }

            $newStatus = $request->post('order_status')->toInt();
            if (!isset(\Product\Order\Model::$statuses[$newStatus])) {
                return $this->errorResponse('Invalid status', 422);
            }
            $oldStatus = (int) $order->order_status;
            $note = $request->post('note')->topic();
            $now = date('Y-m-d H:i:s');

            // Taking an order back out of "cancelled": its stock went back to
            // the shelf when it was cancelled, so it has to come out again —
            // first, so a shortage leaves the order cancelled
            if ($oldStatus === \Product\Order\Model::STATUS_CANCELLED && $newStatus !== \Product\Order\Model::STATUS_CANCELLED) {
                try {
                    \Product\Stock\Model::cutStockForOrder($module->id, $order_id, $this->loadItems($order_id), $login->id);
                } catch (\RuntimeException $e) {
                    return $this->errorResponse(\Kotchasan\Language::get('Insufficient stock').' — '.$e->getMessage(), 409);
                }
            }

            $db = \Kotchasan\DB::create();
            $db->update('product_order', ['id', $order_id], ['order_status' => $newStatus]);
            $db->insert('product_order_status_history', [
                'id' => $db->nextId('product_order_status_history'),
                'order_id' => $order_id,
                'module_id' => $module->id,
                'order_status' => $newStatus,
                'note' => $note,
                'changed_by' => $login->id,
                'created_at' => $now
            ]);

            // Restore stock once when moving into cancelled from a non-cancelled state.
            if ($newStatus === \Product\Order\Model::STATUS_CANCELLED && $oldStatus !== \Product\Order\Model::STATUS_CANCELLED) {
                $items = $this->loadItems($order_id);
                \Product\Stock\Model::returnStockForOrder($module->id, $order_id, $items, $login->id);
            }

            \Index\Log\Model::add($order_id, 'product', 'Product', 'Update order '.$order->order_no.' status -> '.$newStatus, $login->id);

            return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/order/ship
     * Create a shipment row and move the order to "Shipped" (4).
     * Body: module_id, order_id, shipping_method_id, tracking_no, note.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function ship(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $module = $this->resolveModule($login, $request->post('module_id')->toInt());
            if (!$module) {
                return $this->errorResponse('No data available', 404);
            }

            $order_id = $request->post('order_id')->toInt();
            $order = $this->loadOrder($module->id, $order_id);
            if (!$order) {
                return $this->errorResponse('No data available', 404);
            }

            $shipping_method_id = $request->post('shipping_method_id')->toInt();
            $tracking_no = $request->post('tracking_no')->topic();
            $note = $request->post('note')->topic();
            $now = date('Y-m-d H:i:s');

            $db = \Kotchasan\DB::create();
            $db->insert('product_shipment', [
                'id' => $db->nextId('product_shipment'),
                'order_id' => $order_id,
                'module_id' => $module->id,
                'shipping_method_id' => $shipping_method_id,
                'tracking_no' => $tracking_no,
                'status' => 'shipped',
                'shipped_at' => $now,
                'note' => $note,
                'created_by' => $login->id,
                'created_at' => $now
            ]);
            $db->update('product_order', ['id', $order_id], ['order_status' => 4]);
            $db->insert('product_order_status_history', [
                'id' => $db->nextId('product_order_status_history'),
                'order_id' => $order_id,
                'module_id' => $module->id,
                'order_status' => 4,
                'note' => $tracking_no !== '' ? 'Shipped: '.$tracking_no : 'Shipped',
                'changed_by' => $login->id,
                'created_at' => $now
            ]);

            \Index\Log\Model::add($order_id, 'product', 'Product', 'Ship order '.$order->order_no, $login->id);

            return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
