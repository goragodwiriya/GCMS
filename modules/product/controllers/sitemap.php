<?php
/**
 * @filesource modules/product/controllers/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Sitemap;

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
        foreach (\Product\Sitemap\Model::getStories($ids, $date) as $item) {
            $module = $modules[$item->module_id];
            $result[] = (object) array(
                'url' => \Product\Index\Controller::url($module, $item->alias, $item->id, false),
                'date' => date('Y-m-d', $item->last_update)
            );
            $result[] = (object) array(
                'url' => WEB_URL.'amp.php?module='.$module.'&amp;id='.$item->id,
                'date' => date('Y-m-d', $item->last_update)
            );
        }
        return $result;
    }
}
