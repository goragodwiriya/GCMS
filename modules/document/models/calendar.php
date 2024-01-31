<?php
/**
 * @filesource modules/document/models/calendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Calendar;

use Kotchasan\Http\Request;

/**
 *  Model สำหรับอ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ฟังก์ชั่นอ่านข้อมูลสำหรับการแสดงบนปฏิทิน
     *
     * @param array $settings         ค่ากำหนดของปฎิทิน
     * @param int   $first_date       วันที่ 1 (mktime)
     * @param int   $first_next_month วันที่ 1 ของเดือนถัดไป (mktime)
     *
     * @return array
     */
    public function calendar($settings, $first_date, $first_next_month)
    {
        $where = array(
            array('I.create_date', '>=', $first_date),
            array('I.create_date', '<', $first_next_month)
        );
        if (!empty($settings['module']) && preg_match('/^[a-z0-9]+$/', $settings['module'])) {
            $where[] = array('M.module', $settings['module']);
        } elseif (!empty($settings['owner']) && preg_match('/^[a-z0-9]+$/', $settings['owner'])) {
            $where[] = array('M.owner', $settings['owner']);
        } else {
            $where[] = array('M.owner', 'document');
        }
        $where[] = array('I.published', 1);
        $where[] = array('I.index', 0);
        $query = $this->db()->createQuery()
            ->select('I.id', 'I.create_date', 'M.module')
            ->from('index I')
            ->join('modules M', 'INNER', array('M.id', 'I.module_id'))
            ->where($where)
            ->cacheOn()
            ->toArray();
        return $query->execute();
    }

    /**
     * อ่านบทความใน เดือน ปี ที่เลือก สำหรับแสดงในปฏิทิน
     *
     * @param Request $request
     *
     * @return array
     */
    public function widget(Request $request)
    {
        $month = $request->post('month')->toInt();
        $year = $request->post('year')->toInt();
        $first_date = strtotime($year.'-'.$month.'-1');
        if ($month == 12) {
            $first_next_month = strtotime(($year + 1).'-1-1');
        } else {
            $first_next_month = strtotime($year.'-'.($month + 1).'-1');
        }
        $where = array(
            array('I.index', 0),
            array('I.published', 1),
            array('I.create_date', '>=', $first_date),
            array('I.create_date', '<', $first_next_month)
        );
        if ($request->post('module')->exists()) {
            $where[] = array('M.module', $request->post('module')->filter('a-z'));
        } elseif ($request->post('owner')->exists()) {
            $where[] = array('M.owner', $request->post('owner')->filter('a-z'));
        } else {
            $where[] = array('M.owner', 'document');
        }

        $query = $this->db()->createQuery()
            ->select('D.topic', 'I.create_date')
            ->from('index I')
            ->join('modules M', 'INNER', array('M.id', 'I.module_id'))
            ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('M.id', 'D.module_id')))
            ->where($where)
            ->cacheOn();
        $result = array();
        $titles = array();
        foreach ($query->execute() as $item) {
            $d = date('Y-m-d', $item->create_date);
            $titles[$d][] = $item->topic;
            $result[] = array(
                'start' => $d,
                'url' => WEB_URL.'index.php?module=calendar-'.date('d-m-Y', $item->create_date)
            );
        }
        foreach ($result as $i => $item) {
            $result[$i]['title'] = implode("\n", $titles[$item['start']]);
        }
        return $result;
    }
}
