<?php
/**
 * @filesource Widgets/Map/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Map\Controllers;

use Kotchasan\Language;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * แสดงผล Widget
     *
     * @param array $query_string ข้อมูลที่ส่งมาจากการเรียก Widget
     *
     * @return string
     */
    public function get($query_string)
    {
        $map_height = (int) (empty($query_string['height']) ? self::$cfg->map_height : $query_string['height']);
        $q = 'zoom='.(int) (empty($query_string['zoom']) ? self::$cfg->map_zoom : $query_string['zoom']);
        $q .= '&latitude='.(empty($query_string['lat']) ? self::$cfg->map_latitude : $query_string['lat']);
        $q .= '&lantitude='.(empty($query_string['lant']) ? self::$cfg->map_lantitude : $query_string['lant']);
        $q .= '&info='.rawurlencode(empty($query_string['zoom']) ? self::$cfg->map_info : $query_string['info']);
        $q .= '&info_latitude='.(empty($query_string['info_lat']) ? self::$cfg->map_info_latitude : $query_string['info_lat']);
        $q .= '&info_lantitude='.(empty($query_string['info_lant']) ? self::$cfg->map_info_lantitude : $query_string['info_lant']);
        $q .= '&api_key='.self::$cfg->map_api_key;
        $q .= '&lang='.Language::name();
        // คืนค่า HTML
        return '<iframe src="'.WEB_URL.'Widgets/Map/map.php?'.$q.'" style="width:100%;height:'.$map_height.'px"></iframe>';
    }
}
