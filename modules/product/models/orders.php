<?php
/**
 * @filesource modules/product/models/orders.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Orders;

/**
 * Order DataTable Model (admin order list)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Base query for the admin order DataTable.
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [
            ['O.module_id', $params['module_id']]
        ];
        if (isset($params['order_status']) && $params['order_status'] !== '') {
            $where[] = ['O.order_status', (int) $params['order_status']];
        }
        if (isset($params['payment_status']) && $params['payment_status'] !== '') {
            $where[] = ['O.payment_status', $params['payment_status']];
        }

        $query = static::createQuery()
            ->select('O.id', 'O.module_id', 'O.order_no', 'O.created_at', 'O.cust_name', 'O.cust_phone', 'O.grand_total', 'O.order_status', 'O.payment_status', 'O.channel')
            ->from('product_order O')
            ->where($where);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['O.order_no', 'LIKE', $search],
                ['O.cust_name', 'LIKE', $search],
                ['O.cust_phone', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }
}
