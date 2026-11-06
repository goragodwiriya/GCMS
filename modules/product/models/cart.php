<?php
/**
 * @filesource modules/product/models/cart.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Cart;

/**
 * Shopping Cart Model.
 *
 * Identity is either a logged-in member (member_id) or a guest token stored in
 * the `product_cart_token` cookie. The module_id is derived from the product so
 * adding to the cart does not have to send it.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Cookie name for the guest cart token.
     */
    const COOKIE = 'product_cart_token';

    /**
     * The existing cart of a member / guest, without creating one.
     *
     * @param int    $module_id
     * @param int    $member_id
     * @param string $guestToken
     *
     * @return object|null
     */
    public static function findCart($module_id, $member_id, $guestToken)
    {
        if ($member_id <= 0 && $guestToken === '') {
            return null;
        }
        $where = $member_id > 0
            ? [['module_id', $module_id], ['member_id', $member_id]]
            : [['module_id', $module_id], ['member_id', 0], ['guest_token', $guestToken]];

        return \Kotchasan\DB::create()->first('product_cart', $where) ?: null;
    }

    /**
     * Resolve (or create) the active cart for the current identity.
     *
     * @param int $module_id
     * @param int $member_id  0 = guest
     * @param string $guestToken
     *
     * @return object cart row
     */
    public static function resolveCart($module_id, $member_id, $guestToken)
    {
        $db = \Kotchasan\DB::create();
        if ($member_id > 0) {
            $cart = $db->first('product_cart', [['module_id', $module_id], ['member_id', $member_id]]);
            if (!$cart) {
                $id = $db->nextId('product_cart');
                $db->insert('product_cart', ['id' => $id, 'module_id' => $module_id, 'member_id' => $member_id, 'guest_token' => '']);
                $cart = $db->first('product_cart', ['id', $id]);
            }
            return $cart;
        }
        $cart = $db->first('product_cart', [['module_id', $module_id], ['guest_token', $guestToken]]);
        if (!$cart) {
            $id = $db->nextId('product_cart');
            $db->insert('product_cart', ['id' => $id, 'module_id' => $module_id, 'member_id' => 0, 'guest_token' => $guestToken]);
            $cart = $db->first('product_cart', ['id', $id]);
        }
        return $cart;
    }

    /**
     * Resolve module_id and unit price for a product/variant.
     *
     * @param int $product_id
     * @param int $variant_id
     *
     * @return object|null { module_id, price, variant_id, sku }
     */
    public static function resolveProduct($product_id, $variant_id)
    {
        $product = static::createQuery()
            ->select('id', 'module_id', 'product_type', 'base_price', 'published', 'sku')
            ->from('product')
            ->where(['id', $product_id])
            ->first();
        if (!$product || (int) $product->published !== 1) {
            return null;
        }
        if ($variant_id > 0) {
            $variant = static::createQuery()
                ->select('id', 'price', 'sale_price', 'sku')
                ->from('product_variant')
                ->where([['id', $variant_id], ['product_id', $product_id], ['published', 1]])
                ->first();
            if (!$variant) {
                return null;
            }
            $price = $variant->sale_price !== null ? (float) $variant->sale_price : (float) $variant->price;
            return (object) ['module_id' => (int) $product->module_id, 'price' => $price, 'variant_id' => (int) $variant->id, 'sku' => $variant->sku !== '' ? $variant->sku : $product->sku];
        }
        if ($product->product_type === 'variable') {
            // The shopper must pick a variant — falling through would add the
            // first variant at the parent's base price
            return null;
        }
        // simple product -> its implicit variant
        $variant = static::createQuery()
            ->select('id', 'price')
            ->from('product_variant')
            ->where(['product_id', $product_id])
            ->orderBy('id')
            ->first();
        return (object) [
            'module_id' => (int) $product->module_id,
            'price' => (float) $product->base_price,
            'variant_id' => $variant ? (int) $variant->id : 0,
            'sku' => $product->sku
        ];
    }

    /**
     * Add an item to a cart (or increment quantity).
     *
     * @param object $cart
     * @param int    $product_id
     * @param int    $variant_id
     * @param int    $qty
     * @param float  $unit_price
     *
     * @return void
     */
    public static function addItem($cart, $product_id, $variant_id, $qty, $unit_price)
    {
        $db = \Kotchasan\DB::create();
        $existing = $db->first('product_cart_item', [
            ['cart_id', $cart->id],
            ['product_id', $product_id],
            ['variant_id', $variant_id]
        ]);
        if ($existing) {
            $db->update('product_cart_item', ['id', $existing->id], ['qty' => (int) $existing->qty + $qty, 'unit_price' => $unit_price]);
        } else {
            $db->insert('product_cart_item', [
                'id' => $db->nextId('product_cart_item'),
                'cart_id' => $cart->id,
                'module_id' => $cart->module_id,
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'qty' => $qty,
                'unit_price' => $unit_price
            ]);
        }
        $db->update('product_cart', ['id', $cart->id], ['updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Update a cart line quantity (qty <= 0 removes it).
     *
     * @param object $cart
     * @param int    $itemId
     * @param int    $qty
     *
     * @return void
     */
    public static function updateItem($cart, $itemId, $qty)
    {
        $db = \Kotchasan\DB::create();
        if ($qty <= 0) {
            $db->delete('product_cart_item', [['id', $itemId], ['cart_id', $cart->id]]);
        } else {
            $db->update('product_cart_item', [['id', $itemId], ['cart_id', $cart->id]], ['qty' => $qty]);
        }
    }

    /**
     * Remove a cart line.
     *
     * @param object $cart
     * @param int    $itemId
     *
     * @return void
     */
    public static function removeItem($cart, $itemId)
    {
        \Kotchasan\DB::create()->delete('product_cart_item', [['id', $itemId], ['cart_id', $cart->id]]);
    }

    /**
     * Total weight (kg) of cart / order lines.
     *
     * @param array $items rows with product_id, variant_id, qty
     *
     * @return float
     */
    public static function weightOf(array $items)
    {
        // Weight of cart / order lines in kg: the variant's own weight, else
        // the product's
        $variantIds = array_filter(array_map(fn($it) => (int) $it['variant_id'], $items));
        $productIds = array_map(fn($it) => (int) $it['product_id'], $items);
        if (empty($productIds)) {
            return 0.0;
        }
        $variantWeight = [];
        if (!empty($variantIds)) {
            foreach (static::createQuery()->select('id', 'weight')->from('product_variant')->where(['id', array_values($variantIds)])->fetchAll() as $r) {
                $variantWeight[(int) $r->id] = (float) $r->weight;
            }
        }
        $productWeight = [];
        foreach (static::createQuery()->select('id', 'weight')->from('product')->where(['id', array_values(array_unique($productIds))])->fetchAll() as $r) {
            $productWeight[(int) $r->id] = (float) $r->weight;
        }
        $total = 0.0;
        foreach ($items as $it) {
            $weight = $variantWeight[(int) $it['variant_id']] ?? 0.0;
            if ($weight <= 0) {
                $weight = $productWeight[(int) $it['product_id']] ?? 0.0;
            }
            $total += $weight * (int) $it['qty'];
        }

        return round($total, 3);
    }

    /**
     * Lines of a cart (with the variant label) for the cart / checkout.
     *
     * @param int $cart_id
     *
     * @return array
     */
    public static function getItems($cart_id)
    {
        $rows = static::createQuery()
            ->select('I.id', 'I.product_id', 'I.variant_id', 'I.qty', 'I.unit_price', 'I.module_id', 'D.topic')
            ->from('product_cart_item I')
            ->join('product_detail D', [['D.id', 'I.product_id'], ['D.module_id', 'I.module_id'], ['D.language', \Product\Index\Model::languages()]])
            ->where(['I.cart_id', $cart_id])
            ->orderBy('I.id')
            ->fetchAll();
        $items = [];
        foreach ($rows as $r) {
            $label = '';
            if ($r->variant_id > 0) {
                $valueIds = [];
                foreach (static::createQuery()->select('attribute_value_id')->from('product_variant_value')->where(['variant_id', $r->variant_id])->fetchAll() as $vv) {
                    $valueIds[] = (int) $vv->attribute_value_id;
                }
                $label = \Product\Attribute\Model::buildVariantLabel($r->module_id, $valueIds);
            }
            $items[] = [
                'key' => (int) $r->id,
                'product_id' => (int) $r->product_id,
                'variant_id' => (int) $r->variant_id,
                'topic' => $r->topic,
                'variant_label' => $label,
                'qty' => (int) $r->qty,
                'price' => (float) $r->unit_price,
                'line_total' => (float) $r->unit_price * (int) $r->qty
            ];
        }
        return $items;
    }

    /**
     * Cart summary: item count and subtotal.
     *
     * @param int $cart_id
     *
     * @return array { count, subtotal }
     */
    public static function summary($cart_id)
    {
        $row = static::createQuery()
            ->select(
                \Kotchasan\Database\Sql::create('COALESCE(SUM(`qty`),0) AS `count`'),
                \Kotchasan\Database\Sql::create('COALESCE(SUM(`qty` * `unit_price`),0) AS `subtotal`')
            )
            ->from('product_cart_item')
            ->where(['cart_id', $cart_id])
            ->first();
        return [
            'count' => $row ? (int) $row->count : 0,
            'subtotal' => $row ? (float) $row->subtotal : 0
        ];
    }

    /**
     * Empty a cart (after checkout).
     *
     * @param int $cart_id
     *
     * @return void
     */
    public static function clear($cart_id)
    {
        \Kotchasan\DB::create()->delete('product_cart_item', ['cart_id', $cart_id], 0);
    }

    /**
     * Merge a guest cart into the member's cart on login.
     *
     * @param int $module_id
     * @param int $member_id
     * @param string $guestToken
     *
     * @return void
     */
    public static function mergeGuestIntoMember($module_id, $member_id, $guestToken)
    {
        if ($member_id <= 0 || $guestToken === '') {
            return;
        }
        $db = \Kotchasan\DB::create();
        $guest = $db->first('product_cart', [['module_id', $module_id], ['guest_token', $guestToken]]);
        if (!$guest) {
            return;
        }
        $member = self::resolveCart($module_id, $member_id, '');
        foreach (self::getItems($guest->id) as $item) {
            self::addItem($member, $item['product_id'], $item['variant_id'], $item['qty'], $item['price']);
        }
        $db->delete('product_cart_item', ['cart_id', $guest->id], 0);
        $db->delete('product_cart', ['id', $guest->id]);
    }
}
