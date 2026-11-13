<?php
/**
 * @filesource widgets/share/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Share\Controllers;

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
        $id = uniqid();

        // รับค่า URL และ Title จาก query_string (ถ้ามี)
        $data_url = !empty($query_string['url']) ? ' data-url="'.htmlspecialchars($query_string['url']).'"' : '';
        $data_title = !empty($query_string['title']) ? ' data-title="'.htmlspecialchars($query_string['title']).'"' : '';

        // share on Facebook, X (Twitter) & LINE
        $widget = '<div id="'.$id.'" class="widget_share">';
        $widget .= '<a href="#" class="fb_share icon-facebook" title="Facebook"'.$data_url.$data_title.'></a>';
        $widget .= '<a href="#" class="twitter_share icon-twitter" title="X (Twitter)"'.$data_url.$data_title.'></a>';
        $widget .= '<a href="#" class="line_share icon-line" title="LINE"'.$data_url.$data_title.'></a>';
        $widget .= '<script>initShareButton("'.$id.'");</script>';
        $widget .= '</div>';

        // คืนค่า HTML
        return $widget;
    }
}
