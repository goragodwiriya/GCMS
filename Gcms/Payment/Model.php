<?php
/**
 * @filesource Gcms/Payment/Model.php
 *
 * Base model for the central payment module.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gcms\Payment;

/**
 * Central Payment base Model.
 *
 * Records a payment against an order row that already exists in some other
 * table — it never creates orders. A concrete destination only needs to
 * extend this class and set two things:
 *
 *   class Model extends \Gcms\Payment\Model
 *   {
 *       const TABLE = 'product_order'; // logical table name
 *   }
 *
 * (a destination on another connection also sets `protected $conn`)
 *
 * Everything else (finding the order, validating the paid amount, writing
 * only columns that actually exist on the destination table) is generic.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
abstract class Model extends \Kotchasan\Model
{
    /**
     * order_status values that are still awaiting payment. A row outside
     * this set is not eligible to receive a payment notification (already
     * paid, cancelled, etc).
     *
     * @return array
     */
    protected static function payableStatuses()
    {
        return [1, 2];
    }

    /**
     * order_status value to set once payment is recorded.
     * Default: 1=pending, 3=paid — a destination with its own status list
     * overrides this (see Product\Payment\Model).
     *
     * @return int
     */
    protected static function paidStatus()
    {
        return 3;
    }

    /**
     * Minimum amount that must be paid for the order to be accepted.
     * Default: the order's own `total` column, when present.
     *
     * @param object $order
     *
     * @return float
     */
    protected static function minimumPaid($order)
    {
        return isset($order->total) ? (float) $order->total : 0.0;
    }

    /**
     * Find the order this payment applies to.
     *
     * Matches by `order_no` alone when given (most specific), otherwise by
     * `email` + `url` (optionally narrowed further by `package`) — the same
     * identifying fields the old customer-facing notify-payment flow relied
     * on. Only columns that actually exist on the destination table are
     * used in the WHERE clause. Returns null on anything ambiguous/empty so
     * callers never accidentally match "everything".
     *
     * @param array $criteria order_no|email|url|package (all optional, but
     *                        order_no or email+url must be present)
     *
     * @return object|null
     */
    public static function findOrder(array $criteria)
    {
        $columns = static::tableColumns(static::TABLE);
        if (empty($columns)) {
            return null;
        }

        $orderNo = isset($criteria['order_no']) ? trim((string) $criteria['order_no']) : '';
        $email = isset($criteria['email']) ? trim((string) $criteria['email']) : '';
        $url = isset($criteria['url']) ? trim((string) $criteria['url']) : '';

        $where = [];
        if ($orderNo !== '' && in_array('order_no', $columns, true)) {
            $where[] = ['order_no', $orderNo];
        } elseif ($email !== '' && $url !== '' && in_array('email', $columns, true) && in_array('url', $columns, true)) {
            $where[] = ['email', $email];
            $where[] = ['url', $url];
            if (!empty($criteria['package']) && in_array('package', $columns, true)) {
                $where[] = ['package', (int) $criteria['package']];
            }
        } else {
            // Not enough identifying information — refuse to guess.
            return null;
        }

        if (in_array('order_status', $columns, true)) {
            $where[] = ['order_status', static::payableStatuses()];
        }

        return static::createQuery()
            ->from(static::TABLE)
            ->where($where)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Record a payment against a matching order.
     *
     * @param array $criteria    see findOrder()
     * @param array $paymentData payment_date|paid|payment_method|payment_ref|comment
     *                           (only keys that exist as columns on the
     *                           destination table are written)
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

        $columns = static::tableColumns(static::TABLE);
        $writable = array_intersect_key($paymentData, array_flip($columns));
        if (in_array('order_status', $columns, true)) {
            $writable['order_status'] = static::paidStatus();
        }

        if (empty($writable)) {
            return [
                'success' => false,
                'message' => \Kotchasan\Language::get('There is no data to save')
            ];
        }

        static::createDB()->update(static::TABLE, ['id', $order->id], $writable);

        return [
            'success' => true,
            'id' => (int) $order->id,
            'order' => $order,
            'message' => \Kotchasan\Language::get('Payment recorded')
        ];
    }
}
