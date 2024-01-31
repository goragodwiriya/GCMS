<?php
/**
 * @filesource modules/friends/models/lists.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Friends\Lists;

use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * ลิสต์รายการบทความ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ลิสต์รายการกระทู้
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function get(Request $request, $index)
    {
        if (isset($index->module_id)) {
            // query
            $where = array(array('Q.module_id', (int) $index->module_id));
            if ($index->province_id > 0) {
                $where[] = array('Q.province_id', $index->province_id);
            }
            if ($index->sex != '') {
                $where[] = array('U.sex', $index->sex);
            }
            // Model
            $model = new static;
            // query จำนวนโพสต์ปกติ
            $query = $model->db()->createQuery()
                ->from('friends Q')
                ->join('user U', 'INNER', array('U.id', 'Q.member_id'))
                ->where(array_merge(array(array('Q.pin', 0)), $where));
            $index->total = $query->cacheOn()->count();
            // ข้อมูลแบ่งหน้า
            $index->page = $request->request('page')->toInt();
            $index->totalpage = ceil($index->total / $index->list_per_page);
            $index->page = max(1, ($index->page > $index->totalpage ? $index->totalpage : $index->page));
            $index->start = $index->list_per_page * ($index->page - 1);
            // query pin
            $select = array(
                'Q.*',
                'U.status',
                'U.sex',
                Sql::create("(CASE WHEN U.`displayname`='' THEN U.`email` ELSE U.`displayname` END) AS `sender`")
            );
            $query->select($select)
                ->order('Q.id DESC')
                ->where(array_merge(array(array('Q.pin', 1)), $where));
            $index->pins = $query->limit($index->pin_per_page)
                ->order('RAND()')
                ->execute();
            $index->items = $query->where(array_merge(array(array('Q.pin', 0)), $where))
                ->order('create_date DESC')
                ->limit($index->list_per_page, $index->start)
                ->cacheOn()
                ->execute();
            // คืนค่า
            return $index;
        }
        return null;
    }
}
