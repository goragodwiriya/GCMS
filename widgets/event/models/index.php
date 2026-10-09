<?php
/**
 * @filesource widgets/event/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Event\Models;

use Kotchasan\Database\Sql;

/**
 * Widget Event Model – pulls up the next upcoming events.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index
{
    /**
     * Retrieve upcoming published events (begin_date today or later),
     * soonest first, for display in the widget.
     *
     * @param int $module_id
     * @param int $limit
     *
     * @return array
     */
    public static function getUpcoming($module_id, $limit = 5)
    {
        return \Kotchasan\Model::createQuery()
            ->select(
                'id',
                'topic',
                'color',
                Sql::DATE_FORMAT('begin_date', '%Y-%m-%d %H:%i', 'begin_date'),
                Sql::DATE_FORMAT('begin_date', '%H:%i', 'begin_time')
            )
            ->from('event')
            ->where([
                ['module_id', $module_id],
                ['published', 1],
                ['published_date', '<=', date('Y-m-d')],
                [Sql::DATE('begin_date'), '>=', date('Y-m-d')]
            ])
            ->orderBy('begin_date', 'ASC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();
    }
}
