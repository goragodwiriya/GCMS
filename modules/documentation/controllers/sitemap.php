<?php
/**
 * @filesource modules/documentation/controllers/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Sitemap;

use Documentation\Index\Controller as Module;
use Gcms\Gcms;

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
        foreach (\Documentation\Sitemap\Model::getStories($ids) as $item) {
            $module = $modules[$item->module_id];
            $result[] = (object) array(
                'url' => Module::url($module, $item->alias, $item->id),
                'date' => date('Y-m-d', $item->create_date)
            );
            $result[] = (object) array(
                'url' => WEB_URL.'amp.php?module='.$module.'&amp;id='.$item->id,
                'date' => date('Y-m-d', $item->create_date)
            );
        }
        foreach (\Documentation\Sitemap\Model::getCategories($ids) as $item) {
            $result[] = (object) array(
                'url' => Gcms::createUrl($modules[$item->module_id], '', $item->category_id),
                'date' => $date
            );
        }
        return $result;
    }
}
