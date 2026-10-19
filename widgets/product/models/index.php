<?php
/**
 * @filesource widgets/product/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Product\Models;

/**
 * Widget Product Model – pulls the store's products
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * Published products of a product module, ordered like the store
     * listing (featured first, then newest), with what the widget shows:
     * thumb (primary picture), price_min/price_max (the variants' range for
     * a product with variants), in_stock and is_new.
     *
     * @param int   $module_id
     * @param int   $limit
     * @param array $category_ids  Restrict to these category_id, empty for every category
     *
     * @return array
     */
    public static function getLatest($module_id, $limit = 5, $category_ids = [])
    {
        $where = [
            ['P.module_id', $module_id],
            ['P.published', 1]
        ];
        if (!empty($category_ids)) {
            // An array value becomes IN (...)
            $where[] = ['P.category_id', $category_ids];
        }

        $items = static::createQuery()
            ->select(
                'P.id',
                'P.alias',
                'P.product_type',
                'P.base_price',
                'P.stock_qty',
                'P.manage_stock',
                'P.created_at',
                'D.topic',
                'D.description'
            )
            ->from('product P')
            ->join('product_detail D', [['D.id', 'P.id'], ['D.module_id', 'P.module_id'], ['D.language', \Product\Index\Model::languages()]])
            ->where($where)
            ->orderBy('P.featured', 'DESC')
            ->orderBy('P.id', 'DESC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();
        if (empty($items)) {
            return [];
        }

        $ids = [];
        foreach ($items as $item) {
            $ids[] = (int) $item->id;
        }
        $thumbs = \Product\Index\Model::primaryImages($ids, $module_id);
        $prices = \Product\Index\Model::priceRanges($ids, $module_id);

        foreach ($items as $item) {
            $item->thumb = $thumbs[$item->id] ?? WEB_URL.'images/no-image.webp';
            if ($item->product_type === 'variable' && isset($prices[$item->id])) {
                $item->price_min = $prices[$item->id]['min'];
                $item->price_max = $prices[$item->id]['max'];
            } else {
                $item->price_min = $item->base_price;
                $item->price_max = $item->base_price;
            }
            $item->in_stock = \Product\Index\Model::inStock($item);
            $item->is_new = \Product\Index\Model::isNew($item->created_at);
        }

        return $items;
    }
}
