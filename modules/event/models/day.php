<?php
/**
 * @filesource modules/event/models/day.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Day;

use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * Public day-listing model — events on a single day. Ports gcms241021
 * Event\Day\Model::get().
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
     * @return object|null $index with ->date and ->items added, or null
     *                      if the ?d= value is not a valid date
     */
    public static function get(Request $request, $index)
    {
        if (!preg_match('/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/', $request->get('d')->toString(), $match)) {
            return null;
        }

        $index->date = sprintf('%04d-%02d-%02d', $match[1], $match[2], $match[3]);

        $index->items = static::createQuery()
            ->select(
                'id',
                'color',
                'topic',
                'description',
                Sql::DATE('begin_date', 'begin_date'),
                Sql::DATE_FORMAT('begin_date', '%H:%i', 'from_time'),
                Sql::DATE('end_date', 'end_date'),
                Sql::DATE_FORMAT('end_date', '%H:%i', 'to_time')
            )
            ->from('event')
            ->where([
                ['module_id', $index->module_id],
                ['published', 1],
                [Sql::YEAR('begin_date'), (int) $match[1]],
                [Sql::MONTH('begin_date'), (int) $match[2]],
                [Sql::DAY('begin_date'), (int) $match[3]]
            ])
            ->orderBy('begin_date', 'ASC')
            ->cacheOn()
            ->fetchAll();

        return $index;
    }
}
