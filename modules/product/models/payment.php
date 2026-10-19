<?php
/**
 * @filesource modules/product/models/payment.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Payment;

/**
 * Product-order payment — records a customer's self-reported payment
 * against an existing `product_order` row.
 *
 * Unlike \Gcms\Payment\Model's default single-table assumption, product
 * keeps payment bookkeeping in a separate `product_payment` table (one row
 * per order, created at checkout) with its own admin verify/reject
 * workflow (modules/product/controllers/order.php). A customer notify only
 * ever moves the order to `payment_status = 'pending_verify'` — it never
 * marks the order as paid on its own; an admin still has to verify the
 * slip, exactly as before this integration.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Gcms\Payment\Model
{
    /**
     * Logical table name (resolves to {prefix}_product_order).
     */
    const TABLE = 'product_order';

    /**
     * Only orders still awaiting payment (order_status 1 = "Pending
     * payment", see Product\Order\Model::$statuses) may receive a
     * customer-submitted payment notification.
     *
     * @return array
     */
    protected static function payableStatuses()
    {
        return [1];
    }

    /**
     * @param object $order
     *
     * @return float
     */
    protected static function minimumPaid($order)
    {
        return isset($order->grand_total) ? (float) $order->grand_total : 0.0;
    }

    /**
     * Record a payment notification against the matching product_order.
     * Updates (or creates, if somehow missing) the order's `product_payment`
     * row and moves `product_order.payment_status` to 'pending_verify' — it
     * deliberately does NOT touch `order_status`/paidStatus(); that only
     * happens once an admin verifies the payment (see
     * Product\Order\Controller::verifyPayment()).
     *
     * @param array $criteria    see Model::findOrder()
     * @param array $paymentData payment_date|paid|payment_method|payment_ref|comment
     *
     * @return array{success: bool, id?: int, order?: object, message: string}
     */
    public static function recordPayment(array $criteria, array $paymentData)
    {
        $order = static::findOrder($criteria);
        if (!$order) {
            return [
                'success' => false,
                'message' => \Kotchasan\Language::get('No order to pay was found, please check the details')
            ];
        }

        $minimum = static::minimumPaid($order);
        $paid = isset($paymentData['paid']) ? (float) $paymentData['paid'] : 0.0;
        if ($minimum > 0 && $paid < $minimum) {
            return [
                'success' => false,
                'message' => \Kotchasan\Language::replace('The amount paid is less than the amount due (:amount)', [':amount' => number_format($minimum, 2)])
            ];
        }

        $db = static::createDB();
        $payment = $db->first('product_payment', ['order_id', $order->id]);
        // The notify form's "payment method" is free text (the bank the
        // customer paid into) — `method` is an ENUM, so under STRICT mode any
        // other text failed the whole notification. Keep it as the account.
        $method = (string) ($paymentData['payment_method'] ?? '');
        $isMethod = in_array($method, ['bank_transfer', 'cod', 'pos'], true);
        $fields = [
            'amount' => $paid,
            'method' => $isMethod ? $method : $order->payment_method,
            'ref' => mb_substr((string) ($paymentData['payment_ref'] ?? ''), 0, 64),
            'note' => $paymentData['comment'] ?? '',
            'status' => 'pending'
        ];
        if (!$isMethod && $method !== '') {
            $fields['bank_account'] = mb_substr($method, 0, 100);
        }
        if ($payment) {
            $db->update('product_payment', ['id', $payment->id], $fields);
            $paymentId = (int) $payment->id;
        } else {
            $paymentId = $db->nextId('product_payment');
            $db->insert('product_payment', ['id' => $paymentId, 'order_id' => $order->id, 'module_id' => $order->module_id] + $fields);
        }

        $db->update('product_order', ['id', $order->id], ['payment_status' => 'pending_verify']);

        return [
            'success' => true,
            'id' => $paymentId,
            'order' => $order,
            'message' => \Kotchasan\Language::get('Payment recorded, awaiting verification by the administrator')
        ];
    }

    /**
     * Store an uploaded slip file under DATA_FOLDER/product/slip/ and link
     * it to a product_payment row. Shared by both the standalone
     * Product\Payment\Controller::notify() flow and Product\Checkout\Controller
     * (which bundles slip upload into the same request as order placement,
     * matching the storefront theme's single-step checkout form), so a slip
     * always ends up in the one place Product\Order\Controller::verifyPayment()
     * and the admin order-detail view already read from.
     *
     * @param int                          $paymentId
     * @param \Kotchasan\Http\UploadedFile $file
     * @param object                       $cfg  site config (img_typies,
     *                                           stored_img_size, stored_img_type)
     *
     * @return bool
     */
    public static function attachSlip($paymentId, $file, $cfg)
    {
        $dir = ROOT_PATH.DATA_FOLDER.'product/slip/';
        if (!\Kotchasan\File::makeDirectory($dir)) {
            return false;
        }
        $name = $paymentId.'-'.time().$cfg->stored_img_type;
        try {
            $file->resizeImage($cfg->img_typies, $dir, $name, $cfg->stored_img_size);
        } catch (\Exception $exc) {
            return false;
        }
        static::createDB()->update('product_payment', ['id', $paymentId], ['slip_image' => $name]);
        return true;
    }
}
