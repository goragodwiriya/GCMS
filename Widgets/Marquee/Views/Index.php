<?php
/**
 * @filesource Widgets/Marquee/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Marquee\Views;

/**
 * Marquee
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Gcms\View
{
    /**
     * Marquee
     *
     * @param array $query_string
     *
     * @return string
     */
    public static function render($query_string)
    {
        if (!empty($query_string['text'])) {
            $id = uniqid();
            $content = '<div id="containner_'.$id.'" class=marquee_containner><div id="scroller_'.$id.'" class=marquee_scroller>'.$query_string['text'].'</div></div>';
            $content .= '<script>new GScroll("containner_'.$id.'","scroller_'.$id.'").play({"scrollto":"'.$query_string['style'].'","speed":'.max(1, (int) $query_string['speed']).'});</script>';
            return $content;
        }
    }
}
