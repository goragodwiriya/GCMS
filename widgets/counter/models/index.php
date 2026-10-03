<?php
/**
 * @filesource widgets/counter/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Counter\Models;

/**
 * Widget Counter Model – visit statistics from the `counter` table.
 *
 * IMPORTANT — column semantics in this table are not uniform:
 *   - `visited` / `pages_view`  page views for the day (incremented every
 *                               hit). This is consistent across ALL rows and
 *                               is the only reliable metric to aggregate.
 *   - `counter`                 NOT used here. Its meaning changed over time:
 *                               historical rows store a *cumulative running
 *                               total* of page views (e.g. 2016-03-24
 *                               counter = 311 = 154 + 157), while rows written
 *                               by the current Index\Counter\Model::init()
 *                               store *per-day unique visitors*. Because of
 *                               that, SUM(counter) is meaningless (billions)
 *                               and there is no dependable all-time unique
 *                               visitor figure — so the widget reports page
 *                               views (`visited`) only.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index
{
    /**
     * Read aggregated page-view statistics (per period + all-time total).
     *
     * @return object {today, yesterday, month, total} — all page views (visited)
     */
    public static function get()
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $monthStart = date('Y-m-01');

        // One aggregate pass over the daily rows. Dates come from date()
        // (safe, fixed Y-m-d format), so they are inlined directly.
        $row = \Kotchasan\Model::createQuery()
            ->selectRaw(
                "SUM(CASE WHEN `date` = '{$today}' THEN visited ELSE 0 END) AS today, "
                ."SUM(CASE WHEN `date` = '{$yesterday}' THEN visited ELSE 0 END) AS yesterday, "
                ."SUM(CASE WHEN `date` >= '{$monthStart}' THEN visited ELSE 0 END) AS month, "
                .'SUM(visited) AS total'
            )
            ->from('counter')
            ->cacheOn()
            ->first();

        return (object) [
            'today' => $row ? (int) $row->today : 0,
            'yesterday' => $row ? (int) $row->yesterday : 0,
            'month' => $row ? (int) $row->month : 0,
            'total' => $row ? (int) $row->total : 0
        ];
    }
}
