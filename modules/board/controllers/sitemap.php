<?php
/**
 * @filesource modules/board/controllers/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Sitemap;

use Board\Index\Controller as Module;

/**
 * sitemap.xml
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * แสดงผล sitemap.xml
     *
     * @param array  $ids     แอเรย์ของ module_id
     * @param array  $modules แอเรย์ของ module ที่ติดตั้งแล้ว
     * @param string $date    วันที่วันนี้
     *
     * @return array
     */
    public function init($ids, $modules, $date)
    {
        $result = array();
        foreach (\Board\Sitemap\Model::getStories($ids) as $item) {
            $result[] = (object) array(
                'url' => Module::url($modules[$item->module_id], $item->id),
                'date' => date('Y-m-d', ($item->comment_date == 0 ? $item->last_update : $item->comment_date))
            );
        }
        return $result;
    }
}
