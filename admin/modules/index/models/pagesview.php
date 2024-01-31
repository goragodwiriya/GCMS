<?php
/**
 * @filesource modules/index/models/pagesview.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Pagesview;

use Kotchasan\Database\Sql;

/**
 * อ่านข้อมูลการเยี่ยมชมในเดือนที่เลือก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลการเยี่ยมชมในเดือนที่เลือก
     *
     * @param string $date
     *
     * @return array
     */
    public static function get($date)
    {
        $datas = array();
        if (preg_match('/^([0-9]+)\-([0-9]+)$/', $date, $match)) {
            $y = (int) $match[1];
            $m = (int) $match[2];
            $model = new static;
            $query = $model->db()->createQuery()
                ->select('date', Sql::SUM('pages_view', 'pages_view'))
                ->from('counter')
                ->where(array(array(Sql::YEAR('date'), $y), array(Sql::MONTH('date'), $m)))
                ->groupBy('date')
                ->order('date ASC')
                ->toArray()
                ->cacheOn();
            foreach ($query->execute() as $item) {
                $datas[$item['date']] = $item;
            }
        }
        return $datas;
    }
}
