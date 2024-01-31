<?php
/**
 * @filesource modules/index/models/category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Category;

use Gcms\Gcms;
use Kotchasan\ArrayTool;

/**
 * อ่านข้อมูลหมวดหมู่ (Frontend)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Orm\Field
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'category G';

    /**
     * อ่านข้อมูลหมวดหมู่ที่เลือก
     *
     * @param int $category_id
     * @param int $module_id
     *
     * @return object|null ข้อมูลที่เลือก (Object) หรือ null หากไม่พบ
     */
    public static function get($category_id, $module_id)
    {
        $query = \Kotchasan\Model::createQuery()
            ->select()
            ->from('category')
            ->where(array(array('category_id', (int) $category_id), array('module_id', (int) $module_id)))
            ->toArray()
            ->limit(1);
        foreach ($query->execute() as $item) {
            $item['topic'] = Gcms::ser2Str($item, 'topic');
            $item['detail'] = Gcms::ser2Str($item, 'detail');
            $item['icon'] = Gcms::ser2Str($item, 'icon');
            return (object) ArrayTool::unserialize($item['config'], $item);
        }
        return null;
    }

    /**
     * อ่านข้อมูลหมวดหมู่ที่สามารถเผยแพร่ได้
     * สำหรับหน้าแสดงรายการหมวดหมู่
     *
     * @param int $module_id
     *
     * @return array คืนค่าแอเรย์ของ Object ไม่มีคืนค่าแอเรย์ว่าง
     */
    public static function all($module_id)
    {
        $query = \Kotchasan\Model::createQuery()
            ->select()
            ->from('category')
            ->where(array(array('module_id', (int) $module_id), array('published', '1')))
            ->order('category_id')
            ->cacheOn()
            ->toArray();
        $result = array();
        foreach ($query->execute() as $item) {
            $item['topic'] = Gcms::ser2Str($item, 'topic');
            $item['detail'] = Gcms::ser2Str($item, 'detail');
            $item['icon'] = Gcms::ser2Str($item, 'icon');
            $result[] = (object) ArrayTool::unserialize($item['config'], $item);
        }
        return $result;
    }

    /**
     * อ่านข้อมูลหมวดหมู่ที่สามารถเผยแพร่ได้
     * สำหรับใส่ select หรือ menu
     *
     * @param int  $module_id
     * @param int  $group_id  (default) หมวดหมู่ทั่วไป, > หมวดหมู่อื่นๆ
     * @param bool $view      true (default) คืนค่ารายการที่เผยแพร่, คืนค่าทั้งหมด
     *
     * @return array
     */
    public static function categories($module_id, $group_id = 0, $view = true)
    {
        $where = array(
            array('module_id', (int) $module_id),
            array('group_id', $group_id)
        );
        if ($view) {
            $where[] = array('published', '1');
        }
        $query = \Kotchasan\Model::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where($where)
            ->order('category_id')
            ->cacheOn()
            ->toArray();
        $result = array();
        foreach ($query->execute() as $item) {
            $result[$item['category_id']] = Gcms::ser2Str($item, 'topic');
        }
        return $result;
    }

    /**
     * อ่านข้อมูลหมวดหมู่ที่สามารถเผยแพร่ได้
     * สำหรับใส่ select หรือ menu
     * แยกตาม group_id
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function getCategoriesWithGroups($module_id)
    {
        $query = \Kotchasan\Model::createQuery()
            ->select('group_id', 'category_id', 'topic')
            ->from('category')
            ->where(array(
                array('module_id', (int) $module_id),
                array('published', '1')
            ))
            ->order('category_id')
            ->cacheOn()
            ->toArray();
        $result = array();
        foreach ($query->execute() as $item) {
            $result[$item['group_id']][$item['category_id']] = Gcms::ser2Str($item, 'topic');
        }
        return $result;
    }
}
