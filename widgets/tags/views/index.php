<?php
/**
 * @filesource widgets/tags/views/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Views;

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Web\View
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
        $content = '<nav id="'.$id.'" class="widget-tags">';
        $content .= implode('', $items);
        $content .= '</nav>';
        return $content;
    }
}
