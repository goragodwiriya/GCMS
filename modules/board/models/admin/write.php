<?php
/**
 * @filesource modules/board/models/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Admin\Write;

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
     * อัปเดตจำนวนกระทู้และความคิดเห็นในหมวดหมู่
     *
     * @param int $module_id
     */
    public static function updateCategories($module_id)
    {
        if (is_int($module_id) && $module_id > 0) {
            $model = new static;
            $sql1 = $model->db()->createQuery()->selectCount()->from('board_q')->where(array(
                array('category_id', 'C.category_id'),
                array('module_id', 'C.module_id')
            ));
            $sql2 = $model->db()->createQuery()->select('id')->from('board_q')->where(array(
                array('category_id', 'C.category_id'),
                array('module_id', 'C.module_id')
            ));
            $sql3 = $model->db()->createQuery()->selectCount()->from('board_r')->where(array(
                array('index_id', 'IN', $sql2),
                array('module_id', 'C.module_id')
            ));
            $model->db()->createQuery()->update('category C')->set(array(
                'C.c1' => $sql1,
                'C.c2' => $sql3
            ))->where(array('C.module_id', $module_id))->execute();
        }
    }
}
