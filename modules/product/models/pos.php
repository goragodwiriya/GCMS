<?php
/**
 * @filesource modules/product/models/pos.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Pos;

/**
 * POS (Point of Sale) Model — cashier product search and price resolution.
 *
 * Prices are always resolved from the database; client supplied prices are
 * never trusted. Stock cutting reuses \Product\Stock\Model (FIFO engine).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Search published products / variants by topic or SKU for the POS register.
     *
     * @param int    $module_id
     * @param string $q
     * @param int    $limit
     *
     * @return array list of { product_id, variant_id, sku, topic, label, price, stock }
     */
    public static function search($module_id, $q, $limit = 30)
    {
        $like = '%'.$q.'%';
        $query = static::createQuery()
            ->select('V.id variant_id', 'V.sku', 'V.price', 'V.sale_price', 'V.stock_qty', 'P.id product_id', 'P.base_price', 'P.product_type', 'P.manage_stock', 'D.topic')
            ->from('product_variant V')
            ->join('product P', [['P.id', 'V.product_id'], ['P.module_id', 'V.module_id']])
            ->join('product_detail D', [['D.id', 'P.id'], ['D.module_id', 'P.module_id'], ['D.language', \Product\Index\Model::languages()]])
            ->where([
                ['V.module_id', $module_id],
                ['P.published', 1],
                ['V.published', 1]
            ]);

        if ($q !== '') {
            $query->where([
                ['D.topic', 'LIKE', $like],
                ['V.sku', 'LIKE', $like],
                ['P.sku', 'LIKE', $like]
            ], 'OR');
        }

        $rows = $query->orderBy('D.topic', 'V.id')->limit($limit)->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $valueIds = [];
            foreach (static::createQuery()->select('attribute_value_id')->from('product_variant_value')->where(['variant_id', $r->variant_id])->fetchAll() as $vv) {
                $valueIds[] = (int) $vv->attribute_value_id;
            }
            $label = \Product\Attribute\Model::buildVariantLabel($module_id, $valueIds);
            if ($r->product_type === 'variable') {
                $price = $r->sale_price !== null ? (float) $r->sale_price : (float) $r->price;
            } else {
                $price = (float) $r->base_price;
            }
            $items[] = [
                'product_id' => (int) $r->product_id,
                'variant_id' => (int) $r->variant_id,
                'sku' => $r->sku,
                'topic' => $r->topic,
                'label' => $label,
                'price' => $price,
                'stock' => $r->manage_stock ? \Product\Stock\Model::available((int) $r->variant_id) : '∞'
            ];
        }
        return $items;
    }

    /**
     * Resolve the authoritative unit price and snapshot for a product/variant.
     *
     * @param int $module_id
     * @param int $product_id
     * @param int $variant_id
     *
     * @return object|null { product_id, variant_id, sku, product_name, variant_label, price }
     */
    public static function resolveItem($module_id, $product_id, $variant_id)
    {
        $row = static::createQuery()
            ->select('V.id variant_id', 'V.sku', 'V.price', 'V.sale_price', 'P.id product_id', 'P.base_price', 'P.product_type', 'D.topic')
            ->from('product_variant V')
            ->join('product P', [['P.id', 'V.product_id'], ['P.module_id', 'V.module_id']])
            ->join('product_detail D', [['D.id', 'P.id'], ['D.module_id', 'P.module_id'], ['D.language', \Product\Index\Model::languages()]])
            ->where([
                ['V.id', $variant_id],
                ['V.product_id', $product_id],
                ['V.module_id', $module_id],
                ['P.published', 1]
            ])
            ->first();
        if (!$row) {
            return null;
        }
        if ($row->product_type === 'variable') {
            $price = $row->sale_price !== null ? (float) $row->sale_price : (float) $row->price;
        } else {
            $price = (float) $row->base_price;
        }
        $valueIds = [];
        foreach (static::createQuery()->select('attribute_value_id')->from('product_variant_value')->where(['variant_id', $variant_id])->fetchAll() as $vv) {
            $valueIds[] = (int) $vv->attribute_value_id;
        }
        return (object) [
            'product_id' => (int) $row->product_id,
            'variant_id' => (int) $row->variant_id,
            'sku' => $row->sku,
            'product_name' => $row->topic,
            'variant_label' => \Product\Attribute\Model::buildVariantLabel($module_id, $valueIds),
            'price' => $price
        ];
    }

    /**
     * Create a paid POS sale directly (no cart). Re-validates every price from
     * the database and rejects when stock is insufficient. The order, its items
     * and the payment are written in a single transaction, after which FIFO
     * stock is cut via \Product\Stock\Model::cutStockForOrder.
     *
     * @param object $module module row (id, config)
     * @param array  $items  [ ['product_id'=>, 'variant_id'=>, 'qty'=>], ... ]
     * @param float  $paid   amount tendered by the customer
     * @param string $custName
     * @param int    $by     cashier (login id)
     *
     * @throws \RuntimeException on empty/invalid cart or insufficient stock
     *
     * @return array { id, order_no, subtotal, grand_total, paid, change }
     */
    public static function checkout($module, $items, $paid, $custName, $by)
    {
        if (empty($items) || !is_array($items)) {
            throw new \RuntimeException('Cart is empty');
        }

        // Re-validate prices + stock from the database first.
        $lines = [];
        $subtotal = 0;
        foreach ($items as $it) {
            $product_id = (int) ($it['product_id'] ?? 0);
            $variant_id = (int) ($it['variant_id'] ?? 0);
            $qty = (int) ($it['qty'] ?? 0);
            if ($product_id <= 0 || $variant_id <= 0 || $qty <= 0) {
                continue;
            }
            $resolved = self::resolveItem($module->id, $product_id, $variant_id);
            if (!$resolved) {
                throw new \RuntimeException('Invalid item');
            }
            if (\Product\Stock\Model::tracksStock($product_id) && \Product\Stock\Model::available($variant_id) < $qty) {
                throw new \RuntimeException(\Kotchasan\Language::get('Insufficient stock').': '.$resolved->product_name);
            }
            $line_total = round($resolved->price * $qty, 2);
            $subtotal += $line_total;
            $lines[] = [
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'product_name' => $resolved->product_name,
                'variant_label' => $resolved->variant_label,
                'sku' => $resolved->sku,
                'unit_price' => $resolved->price,
                'qty' => $qty,
                'line_total' => $line_total
            ];
        }
        if (empty($lines)) {
            throw new \RuntimeException('Cart is empty');
        }

        $grand_total = $subtotal;
        $config = $module->config;
        $prefix = isset($config->order_prefix) ? $config->order_prefix : '';
        $order_no = \Product\Order\Model::generateOrderNo($module->id, $prefix);

        $db = \Kotchasan\DB::create();
        $db->beginTransaction();
        try {
            $order_id = $db->nextId('product_order');
            $db->insert('product_order', [
                'id' => $order_id,
                'module_id' => $module->id,
                'order_no' => $order_no,
                'member_id' => 0,
                'guest_token' => '',
                'channel' => 'pos',
                'order_status' => 5,
                'payment_status' => 'paid',
                'payment_method' => 'pos',
                'payment_date' => date('Y-m-d H:i:s'),
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'discount' => 0,
                'grand_total' => $grand_total,
                'cust_name' => $custName,
                'created_by' => $by,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $savedItems = [];
            foreach ($lines as $line) {
                $itemId = $db->nextId('product_order_item');
                $db->insert('product_order_item', [
                    'id' => $itemId,
                    'order_id' => $order_id,
                    'module_id' => $module->id,
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'],
                    'product_name' => $line['product_name'],
                    'variant_label' => $line['variant_label'],
                    'sku' => $line['sku'],
                    'unit_price' => $line['unit_price'],
                    'qty' => $line['qty'],
                    'line_total' => $line['line_total'],
                    'cost_total' => 0
                ]);
                $savedItems[] = (object) [
                    'id' => $itemId,
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'],
                    'qty' => $line['qty']
                ];
            }

            $db->insert('product_payment', [
                'id' => $db->nextId('product_payment'),
                'order_id' => $order_id,
                'module_id' => $module->id,
                'method' => 'pos',
                'amount' => $grand_total,
                'status' => 'verified',
                'paid_at' => date('Y-m-d H:i:s'),
                'verified_by' => $by,
                'verified_at' => date('Y-m-d H:i:s')
            ]);

            $db->insert('product_order_status_history', [
                'id' => $db->nextId('product_order_status_history'),
                'order_id' => $order_id,
                'module_id' => $module->id,
                'order_status' => 5,
                'note' => 'POS sale',
                'changed_by' => $by,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }

        // Deduct FIFO stock (own transaction).
        \Product\Stock\Model::cutStockForOrder($module->id, $order_id, $savedItems, $by);

        $paid = (float) $paid;
        return [
            'id' => $order_id,
            'order_no' => $order_no,
            'subtotal' => $subtotal,
            'grand_total' => $grand_total,
            'paid' => $paid,
            'change' => max(0, round($paid - $grand_total, 2))
        ];
    }
}
