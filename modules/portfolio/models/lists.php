<?php
/**
 * @filesource modules/portfolio/models/lists.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Lists;

use Kotchasan\Http\Request;

/**
 * Public portfolio listing — ports gcms241021 modules/portfolio/models/lists.php
 * onto the current query-builder style. `created_at` (not the old
 * `create_date`) per install/database.sql.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param Request $request
     * @param object  $index   module data; expects ->module_id, ->config->rows, ->config->cols
     *
     * @return object $index with ->items, ->total, ->page, ->totalpage, ->tag added
     */
    public static function get(Request $request, $index)
    {
        $where = [
            ['module_id', $index->module_id],
            ['published', '1']
        ];

        $index->tag = $request->get('tag')->topic();
        if (mb_strlen($index->tag) > 1) {
            $where[] = ['keywords', 'LIKE', '%'.$index->tag.'%'];
        }

        $countRow = static::createQuery()
            ->from('portfolio')
            ->where($where)
            ->selectCount()
            ->cacheOn()
            ->first();
        $index->total = $countRow ? (int) $countRow->count : 0;

        $perPage = max(1, (int) $index->rows * (int) $index->cols);
        $index->total_pages = max(1, (int) ceil($index->total / $perPage));
        $index->page = max(1, min($request->get('page')->toInt() ?: 1, $index->total_pages));
        $index->start = $perPage * ($index->page - 1);

        $index->items = static::createQuery()
            ->from('portfolio')
            ->where($where)
            ->orderBy('created_at', 'DESC')
            ->limit($perPage, $index->start)
            ->cacheOn()
            ->fetchAll();

        return $index;
    }
}
