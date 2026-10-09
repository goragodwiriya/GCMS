<?php
/**
 * @filesource modules/product/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Write;

/**
 * Product CRUD Model (admin edit form data)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var array
     */
    public static $languages = ['th', 'en'];

    /**
     * A site upgraded from GCMS 11–14 keeps the legacy module's columns on
     * `product` (modules/product/install/upgrade.php copies them, it never
     * drops them): `product_no` (superseded by `sku`), `picture` (superseded
     * by the `product_image` table), `last_update` (superseded by
     * `updated_at`), `visited` (never carried a DEFAULT 0 in the old
     * schema). The upgrader gives such columns a default, but a table
     * migrated by hand may still have them NOT NULL with no default, and a
     * plain INSERT that only sets the current column set fails under STRICT
     * mode ("Field 'product_no' doesn't have a default value"). Filling
     * them defensively, and only when the column actually exists, makes
     * product creation work whatever state the table is in.
     *
     * @return array
     */
    public static function legacyColumnDefaults()
    {
        $columns = static::tableColumns('product');
        $legacy = [
            'product_no' => '',
            'picture' => '',
            'last_update' => time(),
            'visited' => 0
        ];

        return array_intersect_key($legacy, array_flip($columns));
    }

    /**
     * Get product data for the admin editor.
     * $id === 0 returns a blank skeleton for a new product.
     *
     * @param int $id
     * @param int $module_id
     *
     * @return object|false
     */
    public static function get($id, $module_id)
    {
        if ($id === 0) {
            $detail = [];
            foreach (self::$languages as $lng) {
                $detail[$lng] = ['topic' => '', 'description' => '', 'detail' => '', 'keywords' => ''];
            }
            return (object) [
                'id' => 0,
                'module_id' => $module_id,
                'category_id' => 0,
                'sku' => '',
                'product_type' => 'simple',
                'base_price' => 0,
                'manage_stock' => 1,
                'weight' => 0,
                'featured' => 0,
                'published' => 1,
                'alias' => '',
                'detail' => $detail,
                'variants' => [],
                'images' => []
            ];
        }

        $product = static::createQuery()
            ->select()
            ->from('product')
            ->where([['id', $id], ['module_id', $module_id]])
            ->first();

        if (!$product) {
            return false;
        }

        // Multilingual detail rows
        $rows = static::createQuery()
            ->select('language', 'topic', 'description', 'detail', 'keywords')
            ->from('product_detail')
            ->where([['id', $id], ['module_id', $module_id]])
            ->fetchAll();
        $detail = [];
        foreach (self::$languages as $lng) {
            $detail[$lng] = ['topic' => '', 'description' => '', 'detail' => '', 'keywords' => ''];
        }
        $shared = null;
        foreach ($rows as $r) {
            $row = [
                'topic' => $r->topic,
                'description' => $r->description,
                'detail' => $r->detail,
                'keywords' => $r->keywords
            ];
            if ($r->language === '') {
                // Legacy row shared by every language — fills the languages
                // that have no row of their own (saving replaces it)
                $shared = $row;
            } else {
                $detail[$r->language] = $row;
            }
        }
        if ($shared !== null) {
            foreach (self::$languages as $lng) {
                if ($detail[$lng]['topic'] === '') {
                    $detail[$lng] = $shared;
                }
            }
        }
        $product->detail = $detail;

        // Variants + their attribute-value mapping
        $product->variants = self::getVariants($id, $module_id);

        // Images
        $product->images = self::getImages($id, $module_id);

        return $product;
    }

    /**
     * Load variants of a product with their attribute_value_id list
     *
     * @param int $product_id
     * @param int $module_id
     *
     * @return array
     */
    public static function getVariants($product_id, $module_id)
    {
        $variants = static::createQuery()
            ->select('id', 'sku', 'price', 'sale_price', 'stock_qty', 'weight', 'published', 'image_id')
            ->from('product_variant')
            ->where([['product_id', $product_id], ['module_id', $module_id]])
            ->orderBy('id')
            ->fetchAll();

        if (empty($variants)) {
            return [];
        }

        $ids = array_map(fn($v) => (int) $v->id, $variants);
        $maps = static::createQuery()
            ->select('variant_id', 'attribute_id', 'attribute_value_id')
            ->from('product_variant_value')
            ->where([['variant_id', $ids], ['module_id', $module_id]])
            ->fetchAll();
        $byVariant = [];
        foreach ($maps as $m) {
            $byVariant[$m->variant_id][] = [
                'attribute_id' => (int) $m->attribute_id,
                'attribute_value_id' => (int) $m->attribute_value_id
            ];
        }
        foreach ($variants as $v) {
            $v->values = $byVariant[$v->id] ?? [];
        }
        return $variants;
    }

    /**
     * Load product images as [{id, url, name, is_primary, sort}, ...]
     *
     * @param int $product_id
     * @param int $module_id
     *
     * @return array
     */
    public static function getImages($product_id, $module_id)
    {
        $rows = static::createQuery()
            ->select('id', 'filename', 'sort', 'is_primary')
            ->from('product_image')
            ->where([['product_id', $product_id], ['module_id', $module_id]])
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort', 'ASC')
            ->fetchAll();
        $base = WEB_URL.DATA_FOLDER.'product/'.$product_id.'/';
        $images = [];
        foreach ($rows as $r) {
            $images[] = [
                'id' => (int) $r->id,
                'url' => $base.$r->filename,
                'name' => $r->filename,
                'is_primary' => (int) $r->is_primary,
                'sort' => (int) $r->sort
            ];
        }
        return $images;
    }
}
