<?php
/**
 * @filesource Widgets/Facebook/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Facebook\Controllers;

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
        if (empty(self::$cfg->facebook_page)) {
            self::$cfg->facebook_page = \Widgets\Facebook\Models\Settings::defaultSettings();
        }
        foreach (self::$cfg->facebook_page as $key => $value) {
            if (!isset($query_string[$key])) {
                $query_string[$key] = $value;
            }
        }
        // คืนค่า HTML
        return \Widgets\Facebook\Views\Index::render($query_string);
    }
}
