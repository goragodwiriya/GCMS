<?php
/**
 * @filesource modules/product/models/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\View;

use Kotchasan\Language;

/**
 * อ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านสินค้าที่เลือก
     *
     * @param object $index ข้อมูลที่ส่งมา
     *
     * @return object ข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($index)
    {
        // where
        $where = array();
        if (!empty($index->id)) {
            $where[] = array('P.id', $index->id);
        } elseif (!empty($index->alias)) {
            $where[] = array('P.alias', $index->alias);
        }
        if (!empty($index->module_id)) {
            $where[] = array('P.module_id', $index->module_id);
        }
        if (!empty($where)) {
            // select
            $fields = array(
                'M.config',
                'M.module',
                'P.id',
                'P.module_id',
                'P.product_no',
                'P.picture',
                'P.alias',
                'P.last_update',
                'P.visited',
                'P.published',
                'D.topic',
                'D.keywords',
                'D.description',
                'D.detail',
                'R.price',
                'R.net'
            );
            // model
            $model = new static;
            $query = $model->db()->createQuery()
                ->from('product P')
                ->join('modules M', 'INNER', array(array('M.id', 'P.module_id'), array('M.owner', 'product')))
                ->join('product_detail D', 'INNER', array(array('D.id', 'P.id'), array('D.language', array(Language::name(), ''))))
                ->join('product_price R', 'INNER', array('R.id', 'P.id'))
                ->where($where)
                ->toArray();
            if (self::$request->get('visited')->toInt() == 0) {
                $query->cacheOn(false);
            }
            $result = $query->first($fields);
            if ($result) {
                // อัปเดตการเยี่ยมชม
                ++$result['visited'];
                $model->db()->update($model->getTableName('product'), $result['id'], array('visited' => $result['visited']));
                $model->db()->cacheSave(array($result));
                // อัปเดตตัวแปร
                foreach ($result as $key => $value) {
                    switch ($key) {
                        case 'config':
                            $config = @unserialize($value);
                            if (is_array($config)) {
                                foreach ($config as $k => $v) {
                                    $index->$k = $v;
                                }
                            }
                            break;
                        case 'price':
                        case 'net':
                            $index->$key = self::getPrice($value, $index->currency_unit);
                            break;
                        default:
                            $index->$key = $value;
                            break;
                    }
                }
                return $index;
            }
        }
        return null;
    }

    /**
     * @param $value
     * @param $currency_unit
     */
    public static function getPrice($value, $currency_unit)
    {
        $values = @unserialize($value);
        return is_array($values) ? $values : array($currency_unit => (float) $value);
    }
}
