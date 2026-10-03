<?php
/**
 * @filesource modules/product/models/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\View;

use Kotchasan\Language;

/**
 * Product Frontend Detail Model (single product page)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Load a single published product by id or alias for the storefront.
     *
     * @param int    $module_id
     * @param int    $id
     * @param string $alias
     *
     * @return object|false
     */
    public static function get($module_id, $id, $alias = '')
    {
        $where = [
            ['P.module_id', $module_id],
            ['P.published', 1]
        ];
        if ($id > 0) {
            $where[] = ['P.id', $id];
        } elseif ($alias !== '') {
            $where[] = ['P.alias', $alias];
        } else {
            return false;
        }

        // A language-specific detail row wins over the shared '' row (legacy)
        $product = static::createQuery()
            ->select('P.id', 'P.category_id', 'P.sku', 'P.product_type', 'P.base_price', 'P.stock_qty', 'P.manage_stock', 'P.alias', 'P.visited', 'P.created_at', 'D.topic', 'D.description', 'D.detail', 'D.keywords')
            ->from('product P')
            ->join('product_detail D', [['D.id', 'P.id'], ['D.module_id', 'P.module_id'], ['D.language', \Product\Index\Model::languages()]])
            ->where($where)
            ->orderBy('D.language', 'DESC')
            ->first();

        if (!$product) {
            return false;
        }

        // Page views (the legacy module counted them too). updated_at is
        // assigned to itself so its ON UPDATE CURRENT_TIMESTAMP does not turn
        // it into "last viewed".
        $product->visited = (int) $product->visited + 1;
        static::createQuery()
            ->update('product')
            ->set([
                'visited' => \Kotchasan\Database\Sql::raw('`visited` + 1'),
                'updated_at' => \Kotchasan\Database\Sql::raw('`updated_at`')
            ])
            ->where(['id', $product->id])
            ->execute();

        $product->images = \Product\Write\Model::getImages($product->id, $module_id);
        $product->variants = self::getVariantsForDisplay($product->id, $module_id);
        $product->attributes = self::getAttributeMatrix($product->id, $module_id);

        return $product;
    }

    /**
     * Variants with a readable label for the storefront variant selector.
     *
     * @param int $product_id
     * @param int $module_id
     *
     * @return array
     */
    public static function getVariantsForDisplay($product_id, $module_id)
    {
        $variants = static::createQuery()
            ->select('id', 'sku', 'price', 'sale_price', 'stock_qty', 'published', 'image_id')
            ->from('product_variant')
            ->where([['product_id', $product_id], ['module_id', $module_id], ['published', 1]])
            ->orderBy('id')
            ->cacheOn()
            ->fetchAll();
        if (empty($variants)) {
            return [];
        }
        $ids = array_map(fn($v) => (int) $v->id, $variants);
        $maps = static::createQuery()
            ->select('variant_id', 'attribute_id', 'attribute_value_id')
            ->from('product_variant_value')
            ->where([['variant_id', $ids], ['module_id', $module_id]])
            ->cacheOn()
            ->fetchAll();
        $byVariant = [];
        foreach ($maps as $m) {
            $byVariant[$m->variant_id][] = (int) $m->attribute_value_id;
        }
        foreach ($variants as $v) {
            $valueIds = $byVariant[$v->id] ?? [];
            $v->value_ids = $valueIds;
            $v->label = \Product\Attribute\Model::buildVariantLabel($module_id, $valueIds);
        }
        return $variants;
    }

    /**
     * The set of attributes (and the values actually used by this product's variants)
     * so the storefront can render attribute dropdowns.
     *
     * @param int $product_id
     * @param int $module_id
     *
     * @return array
     */
    public static function getAttributeMatrix($product_id, $module_id)
    {
        $rows = static::createQuery()
            ->select('VV.attribute_id', 'VV.attribute_value_id', 'A.name', 'AV.value')
            ->from('product_variant_value VV')
            ->join('product_variant V', [['V.id', 'VV.variant_id'], ['V.product_id', $product_id]])
            ->join('product_attribute A', ['A.id', 'VV.attribute_id'])
            ->join('product_attribute_value AV', ['AV.id', 'VV.attribute_value_id'])
            ->where(['VV.module_id', $module_id])
            ->cacheOn()
            ->fetchAll();

        $lng = Language::name();
        $attrs = [];
        foreach ($rows as $r) {
            $name = json_decode($r->name, true);
            $value = json_decode($r->value, true);
            if (!isset($attrs[$r->attribute_id])) {
                $attrs[$r->attribute_id] = [
                    'id' => (int) $r->attribute_id,
                    'name' => $name[$lng] ?? reset($name),
                    'values' => []
                ];
            }
            $attrs[$r->attribute_id]['values'][(int) $r->attribute_value_id] = [
                'id' => (int) $r->attribute_value_id,
                'text' => $value[$lng] ?? reset($value)
            ];
        }
        // Re-index values
        $out = [];
        foreach ($attrs as $a) {
            $a['values'] = array_values($a['values']);
            $out[] = $a;
        }
        return $out;
    }
}
