<?php
/**
 * @filesource Widgets/Video/Views/Amp.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Video\Views;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Amp extends \Kotchasan\Controller
{
    /**
     * แสดงผล Video (AMP)
     *
     * @param array $videos
     *
     * @return string
     */
    public static function render($videos)
    {
        return '<amp-youtube width="480" height="270" layout=responsive data-videoid="'.$videos[0]['youtube'].'"></amp-youtube>';
    }
}
