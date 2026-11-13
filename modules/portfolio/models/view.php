<?php
/**
 * @filesource modules/portfolio/models/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\View;

use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * Public single-item view — ports gcms241021 modules/portfolio/models/view.php.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param Request $request
     * @param object  $index   module data; expects ->module_id
     *
     * @return object|null $index with the portfolio row's fields added, or
     *                      null if not found
     */
    public static function get(Request $request, $index)
    {
        $item = static::createQuery()
            ->from('portfolio')
            ->where([
                ['id', $request->get('id')->toInt()],
                ['module_id', $index->module_id]
            ])
            ->cacheOn()
            ->first();

        if (!$item) {
            return null;
        }

        static::createDB()->update('portfolio', ['id', $item->id], ['visited' => Sql::create('visited+1')]);

        foreach ($item as $key => $value) {
            $index->$key = $value;
        }

        return $index;
    }
}
