<?php
/**
 * @filesource Widgets/Share/Controllers/Index.php
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
        // share on tweeter & facebook & line
        $widget = '<div id="'.$id.'" class="widget_share'.(empty($query_string['module']) ? '' : '_'.$query_string['module']).'">';
        if (!empty($query_string['module'])) {
            $widget .= '<span><b id="fb_share_count">0</b>SHARE</span>';
            $widget .= '<a href="#" class="fb_share icon-facebook" title="Facebook Share">Facebook</a>';
            $widget .= '<a href="#" class="twitter_share icon-twitter" title="Twitter">Twitter</a>';
            $widget .= '<a href="#" class="line_share icon-line" title="LINE it!">LINE it!</a>';
        } else {
            $widget .= '<a href="#" class="fb_share icon-facebook" title="Facebook Share"></a>';
            $widget .= '<a href="#" class="twitter_share icon-twitter" title="Twitter"></a>';
            $widget .= '<a href="#" class="line_share icon-line" title="LINE it!"></a>';
        }
        $widget .= '<script>';
        $widget .= '$G(window).Ready(function(){';
        $widget .= 'initShareButton("'.$id.'");';
        $widget .= '});';
        $widget .= '</script>';
        $widget .= '</div>';
        // คืนค่า HTML
        return $widget;
    }
}
