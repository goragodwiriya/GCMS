<?php
/**
 * @filesource modules/event/models/calendar.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Calendar;

use Kotchasan\Database\Sql;

/**
 * Public calendar model — queries published events within the date range
 * requested by the Now.js EventCalendar component (visible grid).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param int    $moduleId
     * @param string $start  YYYY-MM-DD (inclusive)
     * @param string $end    YYYY-MM-DD (inclusive)
     *
     * @return array
     */
    public static function get($moduleId, $start, $end)
    {
        return static::createQuery()
            ->select(
                'id',
                'topic',
                'color',
                Sql::DATE_FORMAT('begin_date', '%Y-%m-%d %H:%i', 'begin_date'),
                Sql::DATE_FORMAT('end_date', '%Y-%m-%d %H:%i', 'end_date')
            )
            ->from('event')
            ->where([
                ['module_id', $moduleId],
                ['published', 1],
                ['published_date', '<=', date('Y-m-d')],
                [Sql::DATE('begin_date'), '>=', $start],
                [Sql::DATE('begin_date'), '<=', $end]
            ])
            ->orderBy('begin_date', 'ASC')
            ->cacheOn()
            ->fetchAll();
    }
}
