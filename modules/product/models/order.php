<?php
/**
 * @filesource modules/product/models/order.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Order;

use Kotchasan\Language;

/**
 * Order Model — numeric order_status (matches the storefront contract).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Numeric order statuses -> language keys.
     *
     * @var array
     */
    public static $statuses = [
        1 => 'Pending payment',
        2 => 'Paid',
        3 => 'Processing',
        4 => 'Shipped',
        5 => 'Completed',
        6 => 'Cancelled'
    ];

    /**
     * Status considered "cancelled/returned" (restores stock).
     */
    const STATUS_CANCELLED = 6;

    /**
     * Translated status text.
     *
     * @param int $code
     *
     * @return string
     */
    public static function statusText($code)
    {
        return isset(self::$statuses[$code]) ? Language::get(self::$statuses[$code]) : (string) $code;
    }

    /**
     * Status options for filters/selects.
     *
     * @return array
     */
    public static function statusOptions()
    {
        $options = [];
        foreach (self::$statuses as $value => $text) {
            $options[] = ['value' => (string) $value, 'text' => Language::get($text)];
        }
        return $options;
    }

    /**
     * Generate a human order number: PREFIX + YYYYMMDD + sequence.
     *
     * @param int    $module_id
     * @param string $prefix
     *
     * @return string
     */
    public static function generateOrderNo($module_id, $prefix = '')
    {
        // Unique across the whole site, not per module: payments (the
        // storefront notify form, Gcms\Payment\Model::findOrder()) look an
        // order up by order_no alone. Continues from the highest number of
        // the day rather than COUNT(*), which repeats a number once an order
        // of that day has been deleted.
        $date = date('Ymd');
        $row = static::createQuery()
            ->select('order_no')
            ->from('product_order')
            ->where(['order_no', 'LIKE', $prefix.$date.'-%'])
            ->orderBy('order_no', 'DESC')
            ->first();
        $seq = $row ? (int) substr($row->order_no, strlen($prefix.$date) + 1) + 1 : 1;
        return $prefix.$date.'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Orders for a member (storefront "my orders"), paginated.
     *
     * @param int $module_id
     * @param int $member_id
     * @param int $status   0 = all
     * @param int $page
     * @param int $perPage
     *
     * @return array { orders, order_statuses, pagination }
     */
    public static function getMemberOrders($module_id, $member_id, $status = 0, $page = 1, $perPage = 10)
    {
        $where = [['module_id', $module_id], ['member_id', $member_id]];
        if ($status > 0) {
            $where[] = ['order_status', $status];
        }

        $countRow = static::createQuery()
            ->select(\Kotchasan\Database\Sql::create('COUNT(*) AS `c`'))
            ->from('product_order')
            ->where($where)
            ->first();
        $total = $countRow ? (int) $countRow->c : 0;
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $rows = static::createQuery()
            ->select('id', 'order_no', 'created_at', 'order_status', 'grand_total', 'payment_method')
            ->from('product_order')
            ->where($where)
            ->orderBy('id', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->fetchAll();

        $orders = [];
        foreach ($rows as $r) {
            $orders[] = [
                'id' => (int) $r->id,
                'order_no' => $r->order_no,
                'created_at' => $r->created_at,
                'order_status' => (int) $r->order_status,
                'order_status_text' => self::statusText((int) $r->order_status),
                'total' => (float) $r->grand_total,
                // label (keys bank_transfer / cod / pos in language/)
                'payment_method' => Language::get($r->payment_method)
            ];
        }

        return [
            'orders' => $orders,
            'order_statuses' => self::statusOptions(),
            'pagination' => [
                'page' => $page,
                'pages' => $pages,
                'page_numbers' => range(1, $pages)
            ]
        ];
    }

    /**
     * Full order detail (for storefront order detail or admin).
     * When $member_id is given the order must belong to that member.
     *
     * @param int      $module_id
     * @param int      $order_id
     * @param int|null $member_id
     *
     * @return array|null
     */
    public static function getOrderDetail($module_id, $order_id, $member_id = null)
    {
        $where = [['id', $order_id], ['module_id', $module_id]];
        if ($member_id !== null) {
            $where[] = ['member_id', $member_id];
        }
        $order = static::createQuery()
            ->select()
            ->from('product_order')
            ->where($where)
            ->first();
        if (!$order) {
            return null;
        }

        $items = static::createQuery()
            ->select('product_id', 'variant_id', 'product_name', 'variant_label', 'sku', 'unit_price', 'qty', 'line_total')
            ->from('product_order_item')
            ->where(['order_id', $order_id])
            ->fetchAll();
        $lineItems = [];
        foreach ($items as $it) {
            $lineItems[] = [
                'topic' => $it->product_name.($it->variant_label !== '' ? ' ('.$it->variant_label.')' : ''),
                'product_no' => $it->sku,
                'quantity' => (int) $it->qty,
                'price' => (float) $it->unit_price,
                'total' => (float) $it->line_total
            ];
        }

        $payment = static::createQuery()
            ->select('method', 'ref', 'slip_image', 'paid_at')
            ->from('product_payment')
            ->where(['order_id', $order_id])
            ->orderBy('id', 'DESC')
            ->first();

        return [
            'id' => (int) $order->id,
            'order_no' => $order->order_no,
            'order_status' => (int) $order->order_status,
            'order_status_text' => self::statusText((int) $order->order_status),
            'created_at' => $order->created_at,
            'payment_date' => $order->payment_date,
            'name' => $order->cust_name,
            'phone' => $order->cust_phone,
            'address' => $order->ship_address,
            'province' => $order->ship_province,
            'zipcode' => $order->ship_zipcode,
            'payment_method' => Language::get($order->payment_method),
            'payment_ref' => $payment ? $payment->ref : '',
            'payment_proof' => $payment && $payment->slip_image !== '' ? WEB_URL.DATA_FOLDER.'product/slip/'.$payment->slip_image : '',
            'items' => $lineItems,
            'subtotal' => (float) $order->subtotal,
            'transport' => (float) $order->shipping_fee,
            'discount' => (float) $order->discount,
            'total' => (float) $order->grand_total,
            'notes' => $order->note
        ];
    }
}
