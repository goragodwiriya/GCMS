<?php
/**
 * @filesource Widgets/Like/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Like\Controllers;

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
        $module = empty($query_string['module']) ? '' : $query_string['module'];
        $likes = array('fb-likebox', 'twitter-share');
        $id = uniqid();
        $widget = '<div class="widget_like">';
        foreach ($likes as $item) {
            $widget .= '<div id="'.$id.$item.'"></div>';
        }
        $widget .= '<script>';
        $widget .= 'function setLikeURL(src, url){';
        $widget .= 'var e = $E(src);';
        $widget .= 'if (e) {';
        $widget .= 'var d = (e.contentWindow || e.contentDocument);';
        $widget .= 'd.location.replace(url);';
        $widget .= '}';
        $widget .= '};';
        $widget .= 'function createLikeButton(){';
        $widget .= 'var url = getCurrentURL();';
        $widget .= 'var patt = /(.*)(&|\?)([0-9]+)?/;';
        $widget .= 'var hs = patt.exec(url);';
        $widget .= 'url = encodeURIComponent(hs ? hs[1] : url);';
        $lng = Language::name();
        $a = 'https://www.facebook.com/plugins/like.php?layout='.($module == 'tall' ? 'box_count' : 'button_count').'&node_type=link&show_faces=false&href=';
        $widget .= 'setLikeURL("'.$id.'fb-likebox-iframe", "'.$a.'" + url);';
        $a = 'https://platform.twitter.com/widgets/tweet_button.1404859412.html#count='.($module == 'tall' ? 'vertical' : 'horizontal').'&lang='.$lng.'&url=';
        $widget .= 'setLikeURL("'.$id.'twitter-share-iframe", "'.$a.'" + url);';
        $widget .= '};';
        $widget .= '$G(window).Ready(function(){';
        foreach ($likes as $item) {
            $widget .= 'var div = $G("'.$id.$item.'");';
            $widget .= 'div.style.display="inline-block";';
            $widget .= 'div.style.verticalAlign="middle";';
            $widget .= "var iframe=document.createElement('iframe');";
            $widget .= 'iframe.id="'.$id.$item.'-iframe";';
            if ($module == 'tall') {
                $widget .= 'iframe.width=60;';
                $widget .= 'iframe.height=68;';
            } else {
                $widget .= 'iframe.width=90;';
                $widget .= 'iframe.height=28;';
            }
            $widget .= 'div.appendChild(iframe);';
        }
        $widget .= 'createLikeButton();';
        $widget .= '});';
        $widget .= '</script>';
        $widget .= '</div>';
        // คืนค่า HTML
        return $widget;
    }
}
