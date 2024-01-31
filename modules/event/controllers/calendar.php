<?php
/**
 * @filesource event/controllers/calendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Calendar;

use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Controller สำหรับแสดงปฏิทิน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * แสดงผลปฎิทิน
     *
     * @param Request $request
     * @param string  $model   Model สำหรับ query ข้อมูล
     *
     * @return string
     */
    public static function render(Request $request, $model = 'Event\Calendar\Model')
    {
        // ภาษา
        $lng = Language::getItems(array(
            'Prev Month',
            'Next Month',
            'DATE_SHORT',
            'MONTH_LONG',
            'YEAR_OFFSET'
        ));
        // วันนี้
        $year = (int) date('Y');
        $month = (int) date('n');
        $today = (int) date('j');
        if (preg_match('/^(prev|next)_([0-9]+)_([0-9]+)$/', $request->request('id')->toString(), $match)) {
            // มาจาก Ajax
            $c_mkdate = mktime(0, 0, 0, (int) $match[2] + ($match[1] == 'prev' ? -1 : 1), 1, $match[3]);
            $c_year = (int) date('Y', $c_mkdate);
            $c_month = (int) date('m', $c_mkdate);
        } elseif (preg_match('/^([0-9]+)\-([0-9]+)$/', $request->request('m')->toString(), $match)) {
            // มาจาก URL
            $c_year = (int) $match[1];
            $c_month = (int) $match[2];
        } else {
            // ไม่ได้เลือกวันที่มา แสดงวันที่วันนี้
            $c_year = $year;
            $c_month = $month;
        }
        // วันที่กำลังแสดงผล
        $d = $c_month.'_'.$c_year;
        // วันที่ 1 ของเดือนนี้
        $mkdate = mktime(0, 0, 0, $c_month, 1, $c_year);
        $weekday = date('w', $mkdate);
        $endday = date('t', $mkdate);
        $day = 1;
        // จำนวนวันของเดือนก่อนหน้า
        $days_of_last_month = $c_month == 1 ? date('t', mktime(0, 0, 0, 12, 1, $c_year - 1)) : date('t', mktime(0, 0, 0, $c_month - 1, 1, $c_year));
        // query และจัดกลุ่มข้อมูลตามวันที่
        $events = array();
        foreach ($model::get($c_year, $c_month) as $item) {
            $events[(int) $item->d][] = $item;
        }
        // แสดงปฏิทิน
        $calendar = array();
        $calendar[] = '<div id=event-calendar>';
        $calendar[] = '<div class=header>';
        $calendar[] = '<a class="prev" id="prev_'.$d.'">'.$lng['Prev Month'].'</a>';
        $calendar[] = '<p>'.$lng['MONTH_LONG'][$c_month].' '.($c_year + $lng['YEAR_OFFSET']).'</p>';
        $calendar[] = '<a class="next" id="next_'.$d.'">'.$lng['Next Month'].'</a>';
        $calendar[] = '</div>';
        $calendar[] = '<table id=event-details>';
        $calendar[] = '<thead>';
        $calendar[] = '<tr><th>'.implode('</th><th>', $lng['DATE_SHORT']).'</th></tr>';
        $calendar[] = '</thead>';
        $start = 1;
        $calendar[] = '<tbody>';
        $data = '<tr class=date>';
        while ($start <= $weekday) {
            $data .= '<td class="ex"><span class="d">'.($days_of_last_month - $weekday + $start).'</span></td>';
            ++$start;
        }
        ++$weekday;
        while ($day <= $endday) {
            if ($today == $day && $month == $c_month && $year == $c_year) {
                $c = ' class="current"';
            } elseif ($weekday == 1) {
                $c = ' class="su"';
            } else {
                $c = '';
            }
            $data .= '<td'.$c.'><a class="d">'.$day.'</a><p>';
            if (isset($events[$day])) {
                foreach ($events[$day] as $item) {
                    $data .= '<a href="'.$model::getUri($item->module, $c_year, $c_month, $day).'" class=cuttext style="background-color:'.$item->color.'" title="'.$item->topic.'">'.$item->topic.'</a>';
                }
            }
            $data .= '</p></td>';
            if ($weekday == 7 && $day != $endday) {
                $calendar[] = $data;
                $data = '<tr class="date row">';
                $weekday = 0;
            }
            ++$day;
            ++$weekday;
        }
        $n = 1;
        while ($weekday <= 7) {
            $data .= '<td class="ex"><span class="d">'.$n.'</span></td>';
            ++$weekday;
            ++$n;
        }
        $calendar[] = $data.'</tr>';
        $calendar[] = '</tbody>';
        $calendar[] = '</table>';
        $calendar[] = '</div>';
        return implode('', $calendar);
    }
}
