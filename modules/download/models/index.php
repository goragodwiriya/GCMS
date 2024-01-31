<?php
/**
 * @filesource modules/download/models/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Index;

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
        $where = array(
            array('module_id', (int) $index->module_id)
        );
        if (!empty($index->category_id)) {
            $where[] = array('category_id', $index->category_id);
        }
        // Model
        $model = new static;
        $query = $model->db()->createQuery()
            ->from('download')
            ->where($where);
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
        $query->select('id', 'category_id', 'name', 'ext', 'detail', 'last_update', 'downloads', 'size')
            ->order('last_update DESC')
            ->limit($index->list_per_page, $index->start);
        $index->items = $query->cacheOn()->execute();
        // คืนค่า
        return $index;
    }
}
