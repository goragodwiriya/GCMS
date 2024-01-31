<?php
/**
 * @filesource modules/board/models/stories.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Stories;

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
            if (!empty($index->category_id)) {
                $where[] = array('Q.category_id', is_array($index->category_id) ? $index->category_id : (int) $index->category_id);
            }
            // Model
            $model = new static;
            // query
            $query = $model->db()->createQuery()
                ->from('board_q Q')
                ->where($where);
            // จำนวน
            $index->total = $query->cacheOn()->count();
            // ข้อมูลแบ่งหน้า
            $index->page = $request->request('page')->toInt();
            $index->totalpage = ceil($index->total / $index->list_per_page);
            $index->page = max(1, ($index->page > $index->totalpage ? $index->totalpage : $index->page));
            $index->start = $index->list_per_page * ($index->page - 1);
            // query pin
            $select = array(
                'Q.*',
                'U1.status',
                'U2.status replyer_status',
                Sql::create('(CASE WHEN Q.`comment_date` > 0 THEN Q.`comment_date` ELSE Q.`last_update` END) AS `d`'),
                Sql::create("(CASE WHEN ISNULL(U1.`id`) THEN Q.`email` WHEN U1.`displayname`='' THEN U1.`email` ELSE U1.`displayname` END) AS `sender`"),
                Sql::create("(CASE WHEN ISNULL(U2.`id`) THEN Q.`commentator` WHEN U2.`displayname`='' THEN U2.`email` ELSE U2.`displayname` END) AS `commentator`")
            );
            $query->select($select)
                ->join('user U1', 'LEFT', array('U1.id', 'Q.member_id'))
                ->join('user U2', 'LEFT', array('U2.id', 'Q.commentator_id'))
                ->order('Q.last_update DESC')
                ->where(array_merge(array(array('Q.pin', 1)), $where));
            $index->items = $query->cacheOn()->execute();
            $query->where(array_merge(array(array('Q.pin', 0)), $where))
                ->limit($index->list_per_page, $index->start);
            foreach ($query->cacheOn()->execute() as $item) {
                $index->items[] = $item;
            }
            // คืนค่า
            return $index;
        }
        return null;
    }
}
