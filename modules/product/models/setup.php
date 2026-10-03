<?php
/**
 * @filesource modules/product/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Setup;

/**
 * Product DataTable Model (admin list)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Base query for the admin product DataTable. Joins the current-language
     * detail row for the topic and the primary image filename.
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [
            ['P.module_id', $params['module_id']]
        ];
        if (!empty($params['category_id'])) {
            $where[] = ['P.category_id', $params['category_id']];
        }
        if (isset($params['published']) && $params['published'] !== '') {
            $where[] = ['P.published', (int) $params['published']];
        }

        $query = static::createQuery()
            ->select('P.id', 'P.module_id', 'P.category_id', 'P.sku', 'P.product_type', 'P.base_price', 'P.stock_qty', 'P.manage_stock', 'P.published', 'P.updated_at', 'D.topic')
            ->from('product P')
            ->join('product_detail D', [['D.id', 'P.id'], ['D.module_id', 'P.module_id'], ['D.language', \Product\Index\Model::languages()]])
            ->where($where);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['D.topic', 'LIKE', $search],
                ['P.sku', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }
}
