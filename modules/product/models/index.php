<?php
/**
 * @filesource modules/product/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Index;

/**
 * Product Frontend Listing Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Load published products for the storefront listing, filtered by
     * category ($index->category_id, list of ids) and search text, one page
     * of $index->rows x $index->cols (module config) at a time.
     * Attaches the page to $index->items (+ image_url, price range, in_stock)
     * and total/total_pages/page for the pagination.
     *
     * @param object $index
     *
     * @return object
     */
    public static function getProducts($index)
    {
        $where = [
            ['P.module_id', $index->module_id],
            ['P.published', 1]
        ];
        if (!empty($index->category_id)) {
            $where[] = ['P.category_id', $index->category_id];
        }

        $query = static::createQuery()
            ->from('product P')
            ->join('product_detail D', [['D.id', 'P.id'], ['D.module_id', 'P.module_id'], ['D.language', self::languages()]])
            ->where($where);
        if (!empty($index->search)) {
            $like = '%'.$index->search.'%';
            $query->where([['D.topic', 'LIKE', $like], ['P.sku', 'LIKE', $like]], 'OR');
        }

        $count = clone $query;
        $row = $count->select(\Kotchasan\Database\Sql::create('COUNT(*) AS `c`'))->first();
        $index->total = $row ? (int) $row->c : 0;
        $limit = max(1, (int) ($index->cols ?? 4)) * max(1, (int) ($index->rows ?? 5));
        $index->total_pages = max(1, (int) ceil($index->total / $limit));
        $index->page = max(1, min((int) ($index->page ?? 1), $index->total_pages));

        $items = $query
            ->select('P.id', 'P.category_id', 'P.sku', 'P.product_type', 'P.base_price', 'P.stock_qty', 'P.manage_stock', 'P.alias', 'P.created_at', 'D.topic', 'D.description')
            ->orderBy('P.featured', 'DESC')
            ->orderBy('P.id', 'DESC')
            ->limit($limit, ($index->page - 1) * $limit)
            ->cacheOn()
            ->fetchAll();
        $ids = [];
        foreach ($items as $item) {
            $ids[] = (int) $item->id;
        }

        $primary = self::primaryImages($ids, $index->module_id);
        $priceRange = self::priceRanges($ids, $index->module_id);

        foreach ($items as $item) {
            $item->image_url = $primary[$item->id] ?? WEB_URL.'images/no-image.webp';
            if ($item->product_type === 'variable' && isset($priceRange[$item->id])) {
                $item->price_min = $priceRange[$item->id]['min'];
                $item->price_max = $priceRange[$item->id]['max'];
            } else {
                $item->price_min = $item->base_price;
                $item->price_max = $item->base_price;
            }
            $item->in_stock = self::inStock($item);
        }

        $index->items = $items;
        return $index;
    }

    /**
     * A product added within this many days gets the "New" badge
     */
    const NEW_DAYS = 30;

    /**
     * Is the product new (created within NEW_DAYS)?
     *
     * @param string|null $createdAt
     *
     * @return bool
     */
    public static function isNew($createdAt)
    {
        $time = $createdAt ? strtotime($createdAt) : false;

        return $time !== false && $time > time() - self::NEW_DAYS * 86400;
    }

    /**
     * product_detail.language values to read for the current language —
     * '' is a row shared by every language (products from the legacy module).
     *
     * @return array
     */
    public static function languages()
    {
        // Language::name() rather than the LANGUAGE constant: API controllers
        // that skip initLanguage() (e.g. stock/get) never define it
        return ['', defined('LANGUAGE') ? LANGUAGE : \Kotchasan\Language::name()];
    }

    /**
     * Can the product be sold now? Products that do not track stock
     * (manage_stock = 0, e.g. downloads, every product upgraded from the
     * legacy module) are always available.
     *
     * @param object $product needs manage_stock and stock_qty
     *
     * @return bool
     */
    public static function inStock($product)
    {
        return empty($product->manage_stock) || (int) $product->stock_qty > 0;
    }

    /**
     * Map product_id => primary image URL
     *
     * @param array $ids
     * @param int   $module_id
     *
     * @return array
     */
    public static function primaryImages($ids, $module_id)
    {
        if (empty($ids)) {
            return [];
        }
        $rows = static::createQuery()
            ->select('product_id', 'filename')
            ->from('product_image')
            ->where([['product_id', $ids], ['module_id', $module_id]])
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort', 'ASC')
            ->cacheOn()
            ->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            if (!isset($map[$r->product_id])) {
                $map[$r->product_id] = WEB_URL.DATA_FOLDER.'product/'.$r->product_id.'/'.$r->filename;
            }
        }
        return $map;
    }

    /**
     * Map product_id => ['min'=>, 'max'=>] of the selling price (sale price
     * when set) across published variants
     *
     * @param array $ids
     * @param int   $module_id
     *
     * @return array
     */
    public static function priceRanges($ids, $module_id)
    {
        if (empty($ids)) {
            return [];
        }
        $rows = static::createQuery()
            ->select(
                'product_id',
                \Kotchasan\Database\Sql::create('MIN(COALESCE(`sale_price`, `price`)) AS `min_price`'),
                \Kotchasan\Database\Sql::create('MAX(COALESCE(`sale_price`, `price`)) AS `max_price`')
            )
            ->from('product_variant')
            ->where([['product_id', $ids], ['module_id', $module_id], ['published', 1]])
            ->groupBy('product_id')
            ->cacheOn()
            ->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->product_id] = ['min' => $r->min_price, 'max' => $r->max_price];
        }
        return $map;
    }
}
