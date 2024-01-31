<?php
/**
 * @filesource modules/index/models/adminmodule.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Adminmodule;

use Kotchasan\ArrayTool;

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
     * อ่านข้อมูลโมดูล และ config
     *
     * @param string $owner     ชื่อโมดูล (ไดเร็คทอรี่)
     * @param int    $module_id
     *
     * @return object|null ข้อมูลโมดูล (Object) หรือ null หากไม่พบ
     */
    public static function getModuleWithConfig($owner, $module_id = -1)
    {
        if ($module_id < 0 && !empty($owner)) {
            $where = array('owner', $owner);
        } elseif (empty($owner)) {
            $where = array('id', $module_id);
        } else {
            $where = array(
                array('owner', $owner),
                array('id', $module_id)
            );
        }
        $model = new static;
        // ตรวจสอบโมดูลที่เรียก
        $index = $model->db()->createQuery()
            ->from('modules')
            ->where($where)
            ->toArray()
            ->first('id module_id', 'module', 'owner', 'config');
        if ($index) {
            // ค่าติดตั้งเริ่มต้น
            $className = ucfirst($index['owner']).'\Admin\Settings\Model';
            if (class_exists($className) && method_exists($className, 'defaultSettings')) {
                $index['config'] = ArrayTool::unserialize($index['config'], $className::defaultSettings());
            } else {
                $index['config'] = ArrayTool::unserialize($index['config']);
            }
            $index = ArrayTool::replace($index['config'], $index);
            unset($index['config']);
            return (object) $index;
        }
        return null;
    }

    /**
     * อ่านชื่อโมดูลจาก $module_id
     *
     * @param int    $module_id
     * @param string $owner
     *
     * @return string
     */
    public static function getModule($module_id, $owner)
    {
        $model = new static;
        $index = $model->db()->createQuery()
            ->from('modules')
            ->where(array(
                array('id', $module_id),
                array('owner', $owner)
            ))
            ->toArray()
            ->cacheOn()
            ->first('module');
        return $index ? $index['module'] : null;
    }
}
