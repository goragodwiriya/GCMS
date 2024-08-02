<?php
/**
 * @filesource Widgets/Counter/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Counter\Controllers;

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
        $counter = \Widgets\Counter\Models\Index::get();
        $fmt = '%0'.self::$cfg->counter_digit.'d';
        // กรอบ counter
        $widget = '<div id=counter-box>';
        $widget .= '<p class=counter-detail><span class=col>{LNG_Total visitors}</span><span id=counter>'.sprintf($fmt, $counter->counter).'</span></p>';
        $widget .= '<p class=counter-detail><span class=col>{LNG_Visitors today}</span><span id=counter_today>'.sprintf($fmt, $counter->visited).'</span></p>';
        $widget .= '<p class=counter-detail><span class=col>{LNG_Pages view}</span><span id=pages_view>'.sprintf($fmt, $counter->pages_view).'</span></p>';
        $widget .= '<p class=counter-detail><span class=col>{LNG_People online}</span><span id=useronline>'.sprintf($fmt, $counter->useronline).'</span></p>';
        $widget .= '</div>';
        // คืนค่า HTML
        return $widget;
    }
}
