<?php
/**
 * @filesource modules/index/models/feed.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Feed;

use Kotchasan\Language;

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
     * ตรวจสอบโมดูลที่เรียก
     *
     * @return array
     */
    public static function getModule($module, $date)
    {
        if (defined('MAIN_INIT')) {
            $model = new static;
            return $model->db()->createQuery()
                ->from('index I')
                ->join('modules M', 'INNER', array('M.id', 'I.module_id'))
                ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id'), array('D.language', array(Language::name(), ''))))
                ->where(array(array('I.index', 1), array('M.module', $module), array('I.published', 1), array('I.published_date', '<=', $date)))
                ->cacheOn()
                ->first('M.id module_id', 'M.module', 'M.owner', 'D.topic', 'D.description', 'M.config');
        } else {
            // เรียก method โดยตรง
            new \Kotchasan\Http\NotFound('Do not call method directly');
        }
    }
}
