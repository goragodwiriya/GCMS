<?php
/**
 * @filesource Widgets/Twitter/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Twitter\Views;

/**
 * Twitter
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Gcms\View
{
    /**
     * Twitter
     *
     * @param array $query_string
     *
     * @return string
     */
    public static function render($query_string)
    {
        if (!empty($query_string['user'])) {
            $content = '<a class="twitter-timeline"';
            $content .= ' href="https://twitter.com/'.$query_string['user'].'"';
            $content .= ' data-link-color="'.$query_string['link_color'].'"';
            $content .= ' data-border-color="'.$query_string['border_color'].'"';
            $content .= ' data-theme="'.$query_string['theme'].'"';
            $content .= ' height="'.$query_string['height'].'"';
            if (!empty($query_string['amount'])) {
                $content .= ' data-tweet-limit="'.$query_string['amount'].'"';
            }
            $content .= '>Tweets by @'.$query_string['user'].'</a>';
            $content .= '<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?"http":"https";';
            $content .= 'if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}';
            $content .= '}(document,"script","twitter-wjs");</script>';
            // คืนค่า HTML
            return $content;
        }
    }
}
