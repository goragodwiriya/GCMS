<?php
/**
 * @filesource modules/product/models/lists.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Lists;

/**
 * Storefront read model for the public list/filter/carousel APIs.
 *
 * Output field names match the shipped Now.js storefront contract
 * (id, url, thumb, topic, page, price, stock).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * `stock` reported for a product that does not track stock
     */
    const STOCK_UNLIMITED = 999999;

    /**
     * Public product list for carousels / related products.
     *
     * @param int    $module_id
     * @param string $module     module name (for URL building)
     * @param array  $categoryIds
     * @param string $search
     * @param int    $limit
     * @param int    $offset
     * @param int    $exclude    product id left out (the product being viewed)
     *
     * @return array list of item arrays; `stock` > 0 means it can be added to
     *               the cart straight from the list (the theme's `data-if`) —
     *               products that do not track stock report STOCK_UNLIMITED,
     *               variable products 0 (the variant is chosen on their page)
     */
    public static function getList($module_id, $module, $categoryIds = [], $search = '', $limit = 10, $offset = 0, $exclude = 0)
    {
        $where = [
            ['P.module_id', $module_id],
            ['P.published', 1]
        ];
        if (!empty($categoryIds)) {
            $where[] = ['P.category_id', $categoryIds];
        }
        if ($exclude > 0) {
            $where[] = ['P.id', '!=', $exclude];
        }
        $query = static::createQuery()
            ->select('P.id', 'P.alias', 'P.product_type', 'P.base_price', 'P.stock_qty', 'P.manage_stock', 'P.created_at', 'D.topic')
            ->from('product P')
            ->join('product_detail D', [['D.id', 'P.id'], ['D.module_id', 'P.module_id'], ['D.language', \Product\Index\Model::languages()]])
            ->where($where);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where([['D.topic', 'LIKE', $like], ['P.sku', 'LIKE', $like]], 'OR');
        }
        $query->orderBy('P.featured', 'DESC')->orderBy('P.id', 'DESC');
        if ($limit > 0) {
            $query->limit($limit, $offset);
        }
        $rows = $query->cacheOn()->fetchAll();

        $ids = array_map(fn($r) => (int) $r->id, $rows);
        $thumbs = \Product\Index\Model::primaryImages($ids, $module_id);
        $prices = \Product\Index\Model::priceRanges($ids, $module_id);

        $items = [];
        foreach ($rows as $r) {
            $isVariable = $r->product_type === 'variable';
            $price = $isVariable && isset($prices[$r->id]) ? $prices[$r->id]['min'] : $r->base_price;
            if ($isVariable) {
                $stock = 0;
            } else {
                $stock = empty($r->manage_stock) ? self::STOCK_UNLIMITED : (int) $r->stock_qty;
            }
            $items[] = [
                'id' => (int) $r->id,
                'url' => \Product\Index\Controller::url($module, $r->alias, $r->id),
                'thumb' => $thumbs[$r->id] ?? WEB_URL.'images/no-image.webp',
                'topic' => $r->topic,
                'page' => 0,
                'price' => (float) $price,
                'stock' => $stock,
                'is_new' => \Product\Index\Model::isNew($r->created_at)
            ];
        }
        return $items;
    }

    /**
     * Categories for the filter sidebar.
     *
     * @param int $module_id
     *
     * @return array list of { id, topic }
     */
    public static function getFilterCategories($module_id)
    {
        $cats = [];
        foreach (\Product\Category\Model::toOptions($module_id) as $opt) {
            $cats[] = ['id' => (int) $opt['value'], 'topic' => $opt['text']];
        }
        return $cats;
    }
}
