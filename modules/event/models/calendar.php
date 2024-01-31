<?php
/**
 * @filesource event/models/calendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Calendar;

use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * ปฎิทิน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * query Event รายเดือน
     *
     * @param int $year
     * @param int $month
     *
     * @return array
     */
    public static function get($year, $month)
    {
        return static::createQuery()
            ->select('D.id', 'D.topic', 'M.module', 'D.color', Sql::DATE('D.begin_date', 'begin_date'))
            ->from('event D')
            ->join('modules M', 'INNER', array('M.id', 'D.module_id'))
            ->where(array(
                array(Sql::MONTH('D.begin_date'), $month),
                array(Sql::YEAR('D.begin_date'), $year)
            ))
            ->order('begin_date DESC', 'end_date')
            ->cacheOn()
            ->execute();
    }

    /**
     * URL ของปฏิทิน
     *
     * @param string $module
     * @param string $date
     *
     * @return string
     */
    public static function getUri($module, $date)
    {
        return WEB_URL.'index.php?module='.$module.'&d='.$date;
    }

    /**
     * อ่านข้อมูลปฏิทินจากเดือนและปีที่ส่งมา
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function toJSON(Request $request)
    {
        // session, referer
        if ($request->initSession() && $request->isReferer()) {
            $datas = static::get($request->post('year')->toInt(), $request->post('month')->toInt());
            $events = array();
            foreach ($datas as $item) {
                $events[] = array(
                    'title' => $item->topic,
                    'start' => $item->begin_date,
                    'color' => $item->color,
                    'url' => WEB_URL.'index.php?module='.$item->module.'&d='.$item->begin_date
                );
            }
            echo json_encode($events);
        }
    }

    /**
     * คืนค่าปีต่ำสุด
     *
     * @return int
     */
    public static function minYear()
    {
        $search = static::createQuery()
            ->from('event')
            ->cacheOn()
            ->first(Sql::create('YEAR(MIN(`begin_date`)) as `y`'));
        return $search ? $search->y : date('Y');
    }
}
