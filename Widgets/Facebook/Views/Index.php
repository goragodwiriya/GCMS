<?php
/**
 * @filesource Widgets/Facebook/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Facebook\Views;

/**
 * Facebook page
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\View
{
    /**
     * Facebook page
     *
     * @param array $query_string
     *
     * @return string
     */
    public static function render($query_string)
    {
        if (!empty($query_string['user'])) {
            $content = '<div class="fb-page"';
            $content .= ' data-href="https://www.facebook.com/'.$query_string['user'].'/"';
            $content .= ' data-tabs="timeline"';
            $content .= ' data-width="500"';
            if (!empty($query_string['height'])) {
                $content .= ' data-height="'.$query_string['height'].'"';
            }
            $content .= ' data-adapt-container-width="true"';
            $content .= ' data-small-header="'.(empty($query_string['small_header']) ? 'false' : 'true').'"';
            $content .= ' data-hide-cover="'.(empty($query_string['hide_cover']) ? 'true' : 'false').'"';
            $content .= ' data-show-facepile="'.(empty($query_string['show_facepile']) ? 'false' : 'true').'"';
            $content .= '><blockquote cite="https://www.facebook.com/'.$query_string['user'].'/" class="fb-xfbml-parse-ignore"></blockquote>';
            $content .= '</div>';
            $content .= '<script>';
            $content .= '(function(d, id) {';
            $content .= 'if (d.getElementById(id)) return;';
            $content .= 'if (d.getElementById("fb-root") === null) {';
            $content .= 'var div = d.createElement("div");';
            $content .= 'div.id="fb-root";';
            $content .= 'd.body.appendChild(div);';
            $content .= '}';
            $content .= 'var js = d.createElement("script");';
            $content .= 'js.id = id;';
            $content .= 'js.src = "//connect.facebook.net/th_TH/sdk.js#xfbml=1&version=v2.7&appId='.(empty(self::$cfg->facebook_appId) ? '' : self::$cfg->facebook_appId).'";';
            $content .= 'd.getElementsByTagName("head")[0].appendChild(js);';
            $content .= '}(document, "facebook-jssdk"));';
            $content .= '</script>';
            // คืนค่า HTML
            return $content;
        }
    }
}
