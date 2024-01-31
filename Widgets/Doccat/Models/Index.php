<?php
/**
 * @filesource Widgets/Doccat/Models/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Doccat\Models;

use Gcms\Gcms;
use Kotchasan\Language;

/**
 * อ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลหมวดหมู่
     *
     * @param int $module_id
     *
     * @return object|null คืนค่าข้อมูล Object รายการเดียว ไม่พบคืนค่า null
     */
    public static function categories($module_id)
    {
        $result = array();
        if (is_int($module_id) && $module_id > 0) {
            $model = new static;
            $query = $model->db()->createQuery()
                ->select('category_id', 'topic')
                ->from('category')
                ->where(array(array('module_id', $module_id), array('published', '1')))
                ->cacheOn()
                ->toArray()
                ->order('category_id');
            foreach ($query->execute() as $item) {
                $result[] = array(
                    'parent_id' => 0,
                    'id' => (int) $item['category_id'],
                    'topic' => Gcms::ser2Str($item, 'topic')
                );
            }
        }
        return $result;
    }

    /**
     * อ่านหัวข้อ
     *
     * @param int $module_id
     *
     * @return array คืนค่าแอเรย์ของ object ไม่พบคืนค่าแอเรย์ว่าง
     */
    public static function topics($module_id)
    {
        if (is_int($module_id) && $module_id > 0) {
            $model = new static;
            return $model->db()->createQuery()
                ->select('I.category_id parent_id', 'I.id', 'D.topic', 'I.alias')
                ->from('index I')
                ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id')))
                ->where(array(array('I.module_id', $module_id), array('I.index', 0), array('published', '1'), array('D.language', array(Language::name(), ''))))
                ->order('I.category_id', 'I.create_date')
                ->cacheOn()
                ->toArray()
                ->execute();
        }
        return array();
    }
}
