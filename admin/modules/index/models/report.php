<?php
/**
 * @filesource modules/index/models/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Report;

use Kotchasan\Database\Sql;

/**
 * อ่านข้อมูลการเยี่ยมชมในวันที่เลือก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลการเยี่ยมชมในวันที่เลือก
     *
     * @param array $params
     *
     * @return array
     */
    public static function get($params)
    {
        if ($params['h'] > -1) {
            $time1 = sprintf('%02d:00:00', $params['h']);
            $time2 = sprintf('%02d:00:00', $params['h'] + 1);
            $sql = 'SQL(`time`>="'.$params['date'].' '.$time1.'" AND `time`<"'.$params['date'].' '.$time2.'")';
        } else {
            $sql = 'SQL(`time`>="'.$params['date'].' 00:00:00" AND `time`<"'.date('Y-m-d', strtotime($params['date'].'+1 day')).' 00:00:00")';
        }
        $query = static::createQuery()
            ->from('logs')
            ->where($sql);
        if ($params['ip'] == '') {
            $query->select('time', 'ip', Sql::COUNT('*', 'count'), 'url', 'referer', 'user_agent')
                ->groupBy('session_id', 'referer');
        } else {
            $query->select('time', 'ip', 'url', 'referer', 'user_agent')
                ->andWhere(array('ip', $params['ip']));
        }
        return $query;
    }

    /**
     * คืนค่าจำนวน log รายชั่วโมง ตามวันที่เลือก
     *
     * @param string $date
     *
     * @return array
     */
    public static function logPerHour($date)
    {
        $query = static::createQuery()
            ->select(Sql::HOUR('time', 'hour'), Sql::COUNT('*', 'count'))
            ->from('logs')
            ->where(array(Sql::DATE('time'), $date))
            ->groupBy('hour')
            ->cacheOn();
        $result = array();
        foreach ($query->execute() as $item) {
            $result[$item->hour] = $item->count;
        }
        return $result;
    }
}
