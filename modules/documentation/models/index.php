<?php
/**
 * @filesource modules/documentation/models/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Index;

use Gcms\Gcms;
use Kotchasan\Language;

/**
 *  Model สำหรับอ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลหมวดหมู่ที่เลือก และสามารถเผยแพร่ได้
     * ไม่ได้กำหนดหมวดมาคืนค่าหมวดแรก
     *
     * @param int $module_id
     * @param int $category_id
     *
     * @return object|null คืนค่าข้อมูล Object รายการเดียว ไม่พบคืนค่า null
     */
    public static function category($module_id, $category_id)
    {
        if (is_int($module_id) && $module_id > 0) {
            $model = new static;
            if ($category_id > 0) {
                $where = [
                    ['category_id', $category_id],
                    ['module_id', $module_id],
                    ['published', '1']
                ];
            } else {
                $where = [
                    ['module_id', $module_id],
                    ['published', '1']
                ];
            }
            $query = $model->db()->createQuery()
                ->from('category')
                ->where($where)
                ->order('category_id')
                ->toArray()
                ->first('category_id', 'topic');
            if ($query) {
                $query['topic'] = Gcms::ser2Str($query, 'topic');
                return (object) $query;
            }
        }
        return null;
    }

    /**
     * อ่านข้อมูลหมวดหมู่ที่เลือก
     *
     * @param int $module_id
     * @param int $category_id
     *
     * @return array คืนค่าข้อมูลแอเรย์ของ object ไม่พบคืนค่าแอเรย์ว่าง
     */
    public static function get($module_id, $category_id)
    {
        if (is_int($module_id) && $module_id > 0) {
            $model = new static;
            return $model->db()->createQuery()
                ->select('I.id', 'D.topic', 'I.alias', 'D.description')
                ->from('index I')
                ->join('index_detail D', 'INNER', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', 'I.language']])
                ->where([['I.module_id', $module_id], ['I.category_id', $category_id], ['I.published', 1], ['I.language', [Language::name(), '']]])
                ->order('I.create_date')
                ->cacheOn()
                ->execute();
        }
        return null;
    }
}
