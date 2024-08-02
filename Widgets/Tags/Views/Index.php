<?php
/**
 * @filesource Widgets/Tags/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Views;

use Kotchasan\Language;

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Gcms\View
{
    /**
     * แสดง Tags
     *
     * @param array $items
     *
     * @return string
     */
    public static function render($items)
    {
        $id = \Kotchasan\Password::uniqid();
        $content = '<div id="'.$id.'" class=widget-tags>';
        $content .= implode('', $items);
        $content .= '</div>';
        $template = '<div class=tag-tooltip><h5>%TAG%</h5><p>'.Language::get('Clicked').' <em>%COUNT%</em> '.Language::get('Count').'</p></div>';
        $content .= '<script>initTags("'.$id.'", "'.addslashes($template).'")</script>';
        return $content;
    }
}
