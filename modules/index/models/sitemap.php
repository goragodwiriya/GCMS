<?php
/**
 * @filesource modules/index/models/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Sitemap;

/**
 * คลาสสำหรับโหลดรายการโมดูลที่ติดตั้งแล้วทั้งหมด จากฐานข้อมูลของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายชื่อโมดูลทั้งหมดที่ติดตั้งแล้ว
     *
     * @return array
     */
    public static function getModules()
    {
        if (defined('MAIN_INIT')) {
            $model = new static;
            return $model->db()->createQuery()
                ->select('M.id', 'M.module', 'M.owner', 'I.language')
                ->from('modules M')
                ->join('index I', 'LEFT', array(array('I.module_id', 'M.id'), array('I.index', 1)))
                ->where(array('I.published', '1'))
                ->cacheOn()
                ->execute();
        } else {
            // เรียก method โดยตรง
            new \Kotchasan\Http\NotFound('Do not call method directly');
        }
    }
}
