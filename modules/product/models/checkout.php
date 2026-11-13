<?php
/**
 * @filesource modules/product/models/checkout.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Checkout;

/**
 * Checkout Model — customer prefill and order placement from a cart.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Prefill customer/shipping info from the member's user record.
     *
     * @param int $member_id
     *
     * @return array
     */
    public static function getCustomerInfo($member_id)
    {
        $info = [
            'id' => $member_id,
            'name' => '',
            'phone' => '',
            'address' => '',
            'provinceID' => '',
            'province' => '',
            'zipcode' => ''
        ];
        if ($member_id > 0) {
            $user = static::createQuery()
                ->select('id', 'name', 'phone', 'address', 'province', 'provinceID', 'zipcode')
                ->from('user')
                ->where(['id', $member_id])
                ->first();
            if ($user) {
                $info['name'] = $user->name;
                $info['phone'] = $user->phone;
                $info['address'] = $user->address;
                $info['province'] = $user->province;
                // The checkout form's free-text province field is named
                // provinceID — prefill it with the name, not the code
                $info['provinceID'] = $user->province !== '' ? $user->province : (string) \Kotchasan\Province::get($user->provinceID);
                $info['zipcode'] = $user->zipcode;
            }
        }
        return $info;
    }

    /**
     * Place an order from the current cart.
     *
     * @param object $module  module row (id, config)
     * @param string $moduleName
     * @param object $cart
     * @param int    $member_id
     * @param string $guestToken
     * @param array  $data    customer + payment fields
     * @param int    $by      admin id for POS (0 for storefront)
     *
     * @return array { id, order_no }
     *
     * @throws \RuntimeException on empty cart / insufficient stock
     */
    public static function placeOrder($module, $moduleName, $cart, $member_id, $guestToken, $data, $by = 0)
    {
        $items = \Product\Cart\Model::getItems($cart->id);
        if (empty($items)) {
            throw new \RuntimeException('Cart is empty');
        }

        // Price and availability as of now, not as of "add to cart": a
        // product may have been repriced, unpublished or sold out since.
        // Stock is checked before the order exists — cutStockForOrder()
        // runs after the order is committed and would leave it behind.
        $subtotal = 0;
        $need = [];
        foreach ($items as $i => $it) {
            $resolved = \Product\Cart\Model::resolveProduct($it['product_id'], $it['variant_id']);
            if (!$resolved || $resolved->module_id !== (int) $module->id) {
                throw new \RuntimeException(\Kotchasan\Language::get('Product not available').': '.$it['topic']);
            }
            $items[$i]['price'] = $resolved->price;
            $items[$i]['line_total'] = round($resolved->price * $it['qty'], 2);
            $items[$i]['sku'] = $resolved->sku;
            $subtotal += $items[$i]['line_total'];
            if ($it['variant_id'] > 0 && \Product\Stock\Model::tracksStock($it['product_id'])) {
                $need[$it['variant_id']] = ($need[$it['variant_id']] ?? 0) + $it['qty'];
                if (\Product\Stock\Model::available($it['variant_id']) < $need[$it['variant_id']]) {
                    throw new \RuntimeException(\Kotchasan\Language::get('Insufficient stock').': '.$it['topic']);
                }
            }
        }
        // Shipping is priced here, from the chosen method, the fresh subtotal
        // and the weight — never taken from the request
        $shipping_method_id = (int) ($data['shipping_method_id'] ?? 0);
        $shipping_fee = $shipping_method_id > 0
            ? \Product\Shipping\Model::calcFee($shipping_method_id, $subtotal, \Product\Cart\Model::weightOf($items))
            : 0.0;
        $discount = isset($data['discount']) ? (float) $data['discount'] : 0;
        $grand_total = $subtotal + $shipping_fee - $discount;

        $config = $module->config;
        $prefix = isset($config->order_prefix) ? $config->order_prefix : '';
        $order_no = \Product\Order\Model::generateOrderNo($module->id, $prefix);

        $method = in_array(($data['payment_method'] ?? 'bank_transfer'), ['bank_transfer', 'cod', 'pos'], true) ? $data['payment_method'] : 'bank_transfer';
        $channel = $by > 0 ? 'pos' : 'online';

        $db = \Kotchasan\DB::create();
        $db->beginTransaction();
        try {
            $order_id = $db->nextId('product_order');
            $db->insert('product_order', [
                'id' => $order_id,
                'module_id' => $module->id,
                'order_no' => $order_no,
                'member_id' => $member_id,
                'guest_token' => $guestToken,
                'channel' => $channel,
                'order_status' => $channel === 'pos' ? 5 : 1,
                'payment_status' => $channel === 'pos' ? 'paid' : 'unpaid',
                'payment_method' => $method,
                'payment_date' => $channel === 'pos' ? date('Y-m-d H:i:s') : null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping_fee,
                'discount' => $discount,
                'grand_total' => $grand_total,
                'cust_name' => $data['name'] ?? '',
                'cust_phone' => $data['phone'] ?? '',
                'cust_email' => $data['email'] ?? '',
                'ship_address' => $data['address'] ?? '',
                'ship_province' => $data['province'] ?? '',
                'ship_zipcode' => $data['zipcode'] ?? '',
                'shipping_method_id' => $shipping_method_id,
                'note' => $data['comment'] ?? '',
                'created_by' => $by,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $savedItems = [];
            foreach ($items as $it) {
                $itemId = $db->nextId('product_order_item');
                $db->insert('product_order_item', [
                    'id' => $itemId,
                    'order_id' => $order_id,
                    'module_id' => $module->id,
                    'product_id' => $it['product_id'],
                    'variant_id' => $it['variant_id'],
                    'product_name' => $it['topic'],
                    'variant_label' => $it['variant_label'],
                    'sku' => $it['sku'],
                    'unit_price' => $it['price'],
                    'qty' => $it['qty'],
                    'line_total' => $it['line_total'],
                    'cost_total' => 0
                ]);
                $savedItems[] = (object) [
                    'id' => $itemId,
                    'product_id' => $it['product_id'],
                    'variant_id' => $it['variant_id'],
                    'qty' => $it['qty']
                ];
            }

            // Payment record
            $db->insert('product_payment', [
                'id' => $db->nextId('product_payment'),
                'order_id' => $order_id,
                'module_id' => $module->id,
                'method' => $method,
                'amount' => $grand_total,
                'status' => $channel === 'pos' ? 'verified' : 'pending',
                'ref' => $data['payment_ref'] ?? '',
                'paid_at' => $channel === 'pos' ? date('Y-m-d H:i:s') : null
            ]);

            // Status history
            $db->insert('product_order_status_history', [
                'id' => $db->nextId('product_order_status_history'),
                'order_id' => $order_id,
                'module_id' => $module->id,
                'order_status' => $channel === 'pos' ? 5 : 1,
                'note' => 'Order created',
                'changed_by' => $by,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }

        // Deduct FIFO stock (own transaction); rolls back stock only on failure
        \Product\Stock\Model::cutStockForOrder($module->id, $order_id, $savedItems, $by);

        // Empty the cart
        \Product\Cart\Model::clear($cart->id);

        return ['id' => $order_id, 'order_no' => $order_no, 'grand_total' => $grand_total];
    }

    /**
     * The order part of the checkout page (themes/{skin}/product/checkout.html,
     * #checkoutOrder): cart lines, shipping methods priced for this cart,
     * payment choices and totals. Returned in `carts` (one entry) so the page
     * re-renders that part from its <template> whenever a line, the shipping
     * method or the payment choice changes.
     *
     * @param object      $module          product module (id, config)
     * @param object|null $cart            the visitor's cart (null = none yet)
     * @param int         $shippingMethodId chosen method (0 = the first)
     * @param string      $paymentChoice   bank|promptpay|cod ('' = the first)
     *
     * @return array ['carts' => [summary], 'count' => items in the cart]
     */
    public static function summary($module, $cart, $shippingMethodId = 0, $paymentChoice = '')
    {
        $items = $cart ? \Product\Cart\Model::getItems($cart->id) : [];
        $subtotal = 0.0;
        $count = 0;
        $lines = [];
        foreach ($items as $item) {
            $subtotal += (float) $item['line_total'];
            $count += (int) $item['qty'];
            $lines[] = [
                'key' => (int) $item['key'],
                'topic' => $item['topic'],
                'variant' => (string) $item['variant_label'],
                'qty' => (int) $item['qty'],
                'minus' => (int) $item['qty'] - 1,
                'plus' => (int) $item['qty'] + 1,
                'total' => number_format((float) $item['line_total'], 2)
            ];
        }
        // shipping: priced for this cart; the chosen one, else the first
        $methods = [];
        $fee = 0.0;
        $quotes = empty($items) ? [] : \Product\Shipping\Model::quotes($module->id, $subtotal, \Product\Cart\Model::weightOf($items));
        $chosen = in_array($shippingMethodId, array_column($quotes, 'id'), true) ? $shippingMethodId : ($quotes[0]['id'] ?? 0);
        foreach ($quotes as $quote) {
            if ($quote['id'] === $chosen) {
                $fee = (float) $quote['fee'];
            }
            $methods[] = [
                'id' => $quote['id'],
                'name' => $quote['name'],
                'fee' => $quote['fee'] > 0 ? number_format((float) $quote['fee'], 2) : \Kotchasan\Language::get('Free'),
                'checked' => $quote['id'] === $chosen
            ];
        }
        $total = $subtotal + $fee;
        // payment: the ways this store takes money
        $config = $module->config;
        $payments = [];
        if (!isset($config->transfer_enabled) || $config->transfer_enabled) {
            $bankInfo = trim((string) ($config->bank_info ?? ''));
            if ($bankInfo !== '') {
                $payments[] = ['value' => 'bank', 'label' => \Kotchasan\Language::get('Bank transfer'), 'detail' => $bankInfo, 'qr' => ''];
            }
            $promptpayId = (string) ($config->promptpay_id ?? '');
            if ($promptpayId !== '') {
                $payments[] = ['value' => 'promptpay', 'label' => 'PromptPay', 'detail' => '',
                    'qr' => \Gcms\Payment\Controller::promptPayImageUrl($promptpayId, $total)];
            }
        }
        if (!isset($config->cod_enabled) || $config->cod_enabled) {
            $payments[] = ['value' => 'cod', 'label' => \Kotchasan\Language::get('Cash on delivery'), 'detail' => '', 'qr' => ''];
        }
        $values = array_column($payments, 'value');
        $payment = in_array($paymentChoice, $values, true) ? $paymentChoice : ($values[0] ?? '');
        foreach ($payments as &$item) {
            $item['checked'] = $item['value'] === $payment;
        }
        unset($item);

        return [
            'carts' => [[
                'items' => $lines,
                'empty' => empty($lines),
                'subtotal' => number_format($subtotal, 2),
                'shipping' => number_format($fee, 2),
                'total' => number_format($total, 2),
                'methods' => $methods,
                'has_methods' => !empty($methods),
                'payments' => $payments,
                'has_payments' => !empty($payments)
            ]],
            'count' => $count
        ];
    }
}
