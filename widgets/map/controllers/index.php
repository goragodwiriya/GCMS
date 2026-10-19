<?php
/**
 * @filesource widgets/map/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Map\Controllers;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\KBase
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
        // A site that never saved the map settings has no $cfg->map — use the
        // same default height as the settings page (Settings::defaults())
        $map = (array) (self::$cfg->map ?? []);
        $height = empty($map['height']) ? 450 : (int) $map['height'];
        return '<iframe id="map-preview" src="'.WEB_URL.'widgets/map/iframe.html" title="Map preview" sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox" style="width:100%;height:'.$height.'px;border:0;border-radius:6px;display:block"></iframe>';
    }
}
