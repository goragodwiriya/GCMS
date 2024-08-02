<?php
/**
 * @filesource Widgets/Gallery/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Gallery\Controllers;

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
        if (empty(self::$cfg->rss_gallery)) {
            self::$cfg->rss_gallery = \Widgets\Gallery\Models\Settings::defaultSettings();
        }
        return '<div id=rss_gallery></div><script>new RSSGal('.json_encode(self::$cfg->rss_gallery).").show('rss_gallery');</script>";
    }
}
