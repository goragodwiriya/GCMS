<?php
/**
 * @filesource modules/friends/models/admin/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Friends\Admin\Index;

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
     * อ่านข้อมูลโมดูล
     *
     * @param int $module_id
     *
     * @return object|null ข้อมูลโมดูล (Object) หรือ null หากไม่พบ
     */
    public static function module($module_id)
    {
        $model = new static;
        // ตรวจสอบโมดูลที่เรียก
        $index = $model->db()->createQuery()
            ->from('modules')
            ->where(array(
                array('id', $module_id),
                array('owner', 'friends')
            ))
            ->toArray()
            ->first('id module_id', 'module', 'owner', 'config');
        if ($index) {
            // ค่าติดตั้งเริ่มต้น
            $default = array(
                'per_day' => 0,
                'list_per_page' => 10,
                'pin_per_page' => 10,
                'sex_color' => array(
                    'f' => '#F1D0ED',
                    'm' => '#BBDBF2'
                ),
                'can_post' => array(1),
                'moderator' => array(1),
                'can_config' => array(1)
            );
            $default = ArrayTool::unserialize($index['config'], $default);
            unset($index['config']);
            return (object) ArrayTool::replace($default, $index);
        }
        return null;
    }
}
