<?php
/**
 * @filesource modules/product/models/stock.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Stock;

/**
 * FIFO Stock Engine.
 *
 * Source of truth = product_stock_lot.qty_remaining (oldest received_at first).
 * product_variant.stock_qty / product.stock_qty are denormalized caches.
 * Every movement is recorded in product_stock_movement for an auditable ledger
 * that also captures cost of goods sold (COGS).
 *
 * Method names cutStockForNewOrder/updateStockOnStatusChange/decreaseStock/
 * increaseStock are kept for compatibility with the storefront conventions, but
 * the implementation is FIFO/lot based.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Receive stock into a new FIFO lot.
     *
     * @param int    $module_id
     * @param int    $product_id
     * @param int    $variant_id
     * @param int    $qty
     * @param float  $unit_cost
     * @param string $ref
     * @param int    $by
     *
     * @return int new lot id
     */
    public static function receive($module_id, $product_id, $variant_id, $qty, $unit_cost, $ref = '', $by = 0)
    {
        $qty = (int) $qty;
        if ($qty <= 0) {
            return 0;
        }
        $db = \Kotchasan\DB::create();
        $lot_id = $db->nextId('product_stock_lot');
        $db->insert('product_stock_lot', [
            'id' => $lot_id,
            'module_id' => $module_id,
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'qty_in' => $qty,
            'qty_remaining' => $qty,
            'unit_cost' => $unit_cost,
            'received_at' => date('Y-m-d H:i:s'),
            'ref' => $ref
        ]);
        $db->insert('product_stock_movement', [
            'id' => $db->nextId('product_stock_movement'),
            'module_id' => $module_id,
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'lot_id' => $lot_id,
            'type' => 'in',
            'qty' => $qty,
            'unit_cost' => $unit_cost,
            'ref_type' => 'receipt',
            'ref_id' => $lot_id,
            'created_by' => $by,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        self::recomputeCache($variant_id);
        return $lot_id;
    }

    /**
     * Does this product track stock? manage_stock = 0 (downloads, services,
     * every product upgraded from the legacy module) means it is always
     * available and orders never touch the FIFO lots.
     *
     * @param int $product_id
     *
     * @return bool
     */
    public static function tracksStock($product_id)
    {
        static $cache = [];
        $product_id = (int) $product_id;
        if (!isset($cache[$product_id])) {
            $row = static::createQuery()
                ->select('manage_stock')
                ->from('product')
                ->where(['id', $product_id])
                ->first();
            $cache[$product_id] = $row ? (bool) $row->manage_stock : true;
        }
        return $cache[$product_id];
    }

    /**
     * Available stock for a variant (sum of remaining lot quantities).
     *
     * @param int $variant_id
     *
     * @return int
     */
    public static function available($variant_id)
    {
        $row = static::createQuery()
            ->select(\Kotchasan\Database\Sql::create('SUM(`qty_remaining`) AS `qty`'))
            ->from('product_stock_lot')
            ->where(['variant_id', $variant_id])
            ->first();
        return $row ? (int) $row->qty : 0;
    }

    /**
     * Deduct stock for a variant using FIFO (oldest lots first), recording one
     * movement per lot slice. Must be called inside a transaction by the caller.
     * Returns the total COGS for the deducted quantity.
     *
     * @param int    $module_id
     * @param int    $product_id
     * @param int    $variant_id
     * @param int    $qty
     * @param string $ref_type  order|pos
     * @param int    $ref_id
     * @param int    $by
     *
     * @throws \RuntimeException when there is not enough stock
     *
     * @return float cost total (COGS)
     */
    public static function deduct($module_id, $product_id, $variant_id, $qty, $ref_type, $ref_id, $by = 0)
    {
        $need = (int) $qty;
        if ($need <= 0) {
            return 0.0;
        }
        $db = \Kotchasan\DB::create();

        // Oldest lots first (FIFO). NOTE: the query builder has no SELECT ... FOR
        // UPDATE; deductions run inside a transaction (see cutStockForOrder). For
        // very high concurrency, add row locking at the DB layer.
        $lots = static::createQuery()
            ->select('id', 'qty_remaining', 'unit_cost')
            ->from('product_stock_lot')
            ->where([['variant_id', $variant_id], ['qty_remaining', '>', 0]])
            ->orderBy('received_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->fetchAll();

        $cost = 0.0;
        foreach ($lots as $lot) {
            if ($need <= 0) {
                break;
            }
            $take = min($need, (int) $lot->qty_remaining);
            $db->update('product_stock_lot', ['id', $lot->id], ['qty_remaining' => (int) $lot->qty_remaining - $take]);
            $db->insert('product_stock_movement', [
                'id' => $db->nextId('product_stock_movement'),
                'module_id' => $module_id,
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'lot_id' => $lot->id,
                'type' => 'out',
                'qty' => -$take,
                'unit_cost' => $lot->unit_cost,
                'ref_type' => $ref_type,
                'ref_id' => $ref_id,
                'created_by' => $by,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $cost += $take * (float) $lot->unit_cost;
            $need -= $take;
        }

        if ($need > 0) {
            throw new \RuntimeException(\Kotchasan\Language::get('Insufficient stock').' (#'.$variant_id.')');
        }

        self::recomputeCache($variant_id);
        return $cost;
    }

    /**
     * Return stock to inventory (e.g. order cancelled). Creates a new lot at the
     * average cost so FIFO ordering remains consistent.
     *
     * @param int    $module_id
     * @param int    $product_id
     * @param int    $variant_id
     * @param int    $qty
     * @param string $ref_type
     * @param int    $ref_id
     * @param int    $by
     *
     * @return void
     */
    public static function restore($module_id, $product_id, $variant_id, $qty, $ref_type, $ref_id, $by = 0)
    {
        $qty = (int) $qty;
        if ($qty <= 0) {
            return;
        }
        $db = \Kotchasan\DB::create();
        $lot_id = $db->nextId('product_stock_lot');
        $db->insert('product_stock_lot', [
            'id' => $lot_id,
            'module_id' => $module_id,
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'qty_in' => $qty,
            'qty_remaining' => $qty,
            'unit_cost' => self::averageCost($variant_id),
            'received_at' => date('Y-m-d H:i:s'),
            'ref' => 'return'
        ]);
        $db->insert('product_stock_movement', [
            'id' => $db->nextId('product_stock_movement'),
            'module_id' => $module_id,
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'lot_id' => $lot_id,
            'type' => 'return',
            'qty' => $qty,
            'unit_cost' => 0,
            'ref_type' => $ref_type,
            'ref_id' => $ref_id,
            'created_by' => $by,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        self::recomputeCache($variant_id);
    }

    /**
     * Deduct FIFO stock for all items of a newly created order (wrapped in a
     * transaction). Fills product_order_item.cost_total per line.
     *
     * @param int   $module_id
     * @param int   $order_id
     * @param array $items  rows of product_order_item (objects/arrays with product_id, variant_id, qty)
     * @param int   $by
     *
     * @throws \RuntimeException on insufficient stock (transaction rolled back)
     *
     * @return void
     */
    public static function cutStockForOrder($module_id, $order_id, $items, $by = 0)
    {
        $db = \Kotchasan\DB::create();
        $db->beginTransaction();
        try {
            foreach ($items as $item) {
                $item = (object) $item;
                if (empty($item->variant_id) || !self::tracksStock($item->product_id)) {
                    continue;
                }
                $cost = self::deduct($module_id, $item->product_id, $item->variant_id, $item->qty, 'order', $order_id, $by);
                if (!empty($item->id)) {
                    $db->update('product_order_item', ['id', $item->id], ['cost_total' => $cost]);
                }
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Restore FIFO stock for all items of an order (e.g. cancellation).
     *
     * @param int   $module_id
     * @param int   $order_id
     * @param array $items
     * @param int   $by
     *
     * @return void
     */
    public static function returnStockForOrder($module_id, $order_id, $items, $by = 0)
    {
        foreach ($items as $item) {
            $item = (object) $item;
            if (empty($item->variant_id) || !self::tracksStock($item->product_id)) {
                continue;
            }
            self::restore($module_id, $item->product_id, $item->variant_id, $item->qty, 'order', $order_id, $by);
        }
    }

    /**
     * Recompute the denormalized stock cache for a variant and its parent product.
     *
     * @param int $variant_id
     *
     * @return void
     */
    public static function recomputeCache($variant_id)
    {
        $db = \Kotchasan\DB::create();
        $remaining = self::available($variant_id);
        $db->update('product_variant', ['id', $variant_id], ['stock_qty' => $remaining]);

        $variant = $db->first('product_variant', ['id', $variant_id], ['product_id']);
        if ($variant) {
            $sum = static::createQuery()
                ->select(\Kotchasan\Database\Sql::create('SUM(`stock_qty`) AS `qty`'))
                ->from('product_variant')
                ->where(['product_id', $variant->product_id])
                ->first();
            $db->update('product', ['id', $variant->product_id], ['stock_qty' => $sum ? (int) $sum->qty : 0]);
        }
    }

    /**
     * Average remaining cost for a variant (for stock returns).
     *
     * @param int $variant_id
     *
     * @return float
     */
    protected static function averageCost($variant_id)
    {
        $row = static::createQuery()
            ->select(\Kotchasan\Database\Sql::create('SUM(`qty_remaining`) AS `q`'), \Kotchasan\Database\Sql::create('SUM(`qty_remaining` * `unit_cost`) AS `c`'))
            ->from('product_stock_lot')
            ->where([['variant_id', $variant_id], ['qty_remaining', '>', 0]])
            ->first();
        if ($row && (int) $row->q > 0) {
            return round((float) $row->c / (int) $row->q, 2);
        }
        return 0.0;
    }
}
