<?php
/**
 * @filesource modules/index/models/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Export;

/**
 * คลาสสำหรับโหลดรายการเมนูจากฐานข้อมูลของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลโมดูลจาก $module
     *
     * @param string $module
     *
     * @return object|bool คืนค่าข้อมูล object ไม่พบ คืนค่า false
     */
    public static function module($module)
    {
        if (preg_match('/^[a-z]+$/', $module)) {
            $model = new static;
            $result = $model->db()->createQuery()
                ->from('modules')
                ->where(array('module', $module))
                ->cacheOn()
                ->toArray()
                ->first('id module_id', 'module', 'owner', 'config');
            if ($result) {
                if (!empty($result['config'])) {
                    $config = @unserialize($result['config']);
                    if (is_array($config)) {
                        foreach ($config as $key => $value) {
                            $result[$key] = $value;
                        }
                    }
                }
                unset($result['config']);
                return (object) $result;
            }
        }
        return false;
    }
}
