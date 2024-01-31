<?php
/**
 * @filesource modules/documentation/models/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\View;

use Gcms\Gcms;

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
     * อ่านบทความที่เลือก
     *
     * @param object $index ข้อมูลที่ส่งมา
     *
     * @return object ข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($index)
    {
        // model
        $model = new static;
        // select
        $fields = array(
            'I.id',
            'I.module_id',
            'I.category_id',
            'D.topic',
            'D.description',
            'D.detail',
            'D.keywords',
            'D.relate',
            'I.last_update',
            'I.visited',
            'I.alias',
            'I.published',
            'C.topic category'
        );
        // where
        $where = array();
        if (!empty($index->id)) {
            $where[] = array('I.id', $index->id);
        } elseif (!empty($index->alias)) {
            $where[] = array('I.alias', $index->alias);
        }
        $where[] = array('I.index', 0);
        if (!empty($index->module_id)) {
            $where[] = array('I.module_id', $index->module_id);
        }
        $result = $model->db()->createQuery()
            ->from('index I')
            ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id')))
            ->join('category C', 'LEFT', array(array('C.category_id', 'I.category_id'), array('C.module_id', 'I.module_id')))
            ->where($where)
            ->toArray()
            ->cacheOn(false)
            ->first($fields);
        if ($result) {
            ++$result['visited'];
            $model->db()->update($model->getTableName('index'), (int) $result['id'], array('visited' => (int) $result['visited']));
            $model->db()->cacheSave(array($result));
            $result['category'] = Gcms::ser2Str($result, 'category');
            // อัปเดตตัวแปร
            foreach ($result as $key => $value) {
                $index->$key = $value;
            }
            // คืนค่าข้อมูลบทความ

            return $index;
        }
        return null;
    }
}
