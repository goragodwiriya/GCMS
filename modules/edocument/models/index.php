<?php
/**
 * @filesource modules/edocument/models/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Index;

use Kotchasan\Http\Request;

/**
 * อ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลโมดูล
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function getItems(Request $request, $index)
    {
        // Model
        $model = new static;
        $query = $model->db()->createQuery()
            ->from('edocument D')
            ->join('user U', 'INNER', array('U.id', 'D.sender_id'))
            ->where(array('D.module_id', (int) $index->module_id));
        // จำนวน
        $index->total = $query->cacheOn()->count();
        // ข้อมูลแบ่งหน้า
        if (empty($index->list_per_page)) {
            $index->list_per_page = 20;
        }
        $index->page = $request->request('page')->toInt();
        $index->totalpage = ceil($index->total / $index->list_per_page);
        $index->page = max(1, ($index->page > $index->totalpage ? $index->totalpage : $index->page));
        $index->start = $index->list_per_page * ($index->page - 1);
        // query
        $query->select('D.*', 'U.email', 'U.displayname', 'U.status')
            ->order('D.last_update DESC')
            ->limit($index->list_per_page, $index->start);
        $index->items = $query->cacheOn()->execute();
        // คืนค่า
        return $index;
    }
}
