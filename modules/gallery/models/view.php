<?php
/**
 * @filesource modules/gallery/models/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\View;

use Kotchasan\Http\Request;

/**
 * โมเดลสำหรับแสดงรายการอัลบัม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ลิสต์รายการอัลบัม
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function get(Request $request, $index)
    {
        $model = new static;
        // query album
        $album = $model->db()->createQuery()
            ->from('gallery_album')
            ->where(array(
                array('id', $request->request('id')->toInt()),
                array('module_id', $index->module_id)
            ))
            ->cacheOn()
            ->toArray()
            ->first('id', 'topic', 'detail', 'visited', 'last_update');
        if ($album) {
            $index->title = $index->topic;
            foreach ($album as $key => $value) {
                $index->$key = $value;
            }
            // visited
            ++$index->visited;
            $model->db()->update($model->getTableName('gallery_album'), $index->id, array('visited' => $index->visited));
            // query pictures
            $query = $model->db()->createQuery()
                ->from('gallery G')
                ->where(array(
                    array('G.album_id', $index->id),
                    array('G.module_id', $index->module_id)
                ));
            // จำนวน
            $index->total = $query->cacheOn()->count();
            // ข้อมูลแบ่งหน้า
            $list_per_page = $index->rows * $index->cols;
            $index->page = $request->request('page')->toInt();
            $index->totalpage = ceil($index->total / $list_per_page);
            $index->page = max(1, ($index->page > $index->totalpage ? $index->totalpage : $index->page));
            $index->start = $list_per_page * ($index->page - 1);
            // รายการที่แสดง
            $query->select('G.id', 'G.image')->order('count')->limit($list_per_page, $index->start);
            $index->items = $query->cacheOn()->execute();
            return $index;
        }
        return null;
    }
}
