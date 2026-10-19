<?php
/**
 * @filesource modules/video/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Index;

use Kotchasan\Http\Request;

/**
 * Public video listing — ports gcms241021 Video\Index\Model::getItems()
 * onto the current query-builder style (mirrors Portfolio\Lists\Model).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param Request $request
     * @param object  $index   module data; expects ->module_id, ->rows, ->cols
     *
     * @return object $index with ->items, ->total, ->page, ->total_pages added
     */
    public static function get(Request $request, $index)
    {
        $where = [
            ['module_id', $index->module_id]
        ];

        $countRow = static::createQuery()
            ->from('video')
            ->where($where)
            ->selectCount()
            ->cacheOn()
            ->first();
        $index->total = $countRow ? (int) $countRow->count : 0;

        $rows = empty($index->rows) ? 4 : (int) $index->rows;
        $cols = empty($index->cols) ? 4 : (int) $index->cols;
        $perPage = max(1, $rows * $cols);
        $index->total_pages = max(1, (int) ceil($index->total / $perPage));
        $index->page = max(1, min($request->get('page')->toInt() ?: 1, $index->total_pages));
        $index->start = $perPage * ($index->page - 1);

        $index->items = static::createQuery()
            ->select('id', 'youtube', 'topic', 'description', 'views', 'last_update')
            ->from('video')
            ->where($where)
            ->orderBy('last_update', 'DESC')
            ->limit($perPage, $index->start)
            ->cacheOn()
            ->fetchAll();

        return $index;
    }
}
