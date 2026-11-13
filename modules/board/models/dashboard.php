<?php
/**
 * @filesource modules/board/models/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Dashboard;

use Kotchasan\Database\Sql;

/**
 * Board Dashboard Model
 *
 * Provides board statistics for the main dashboard.
 * This file is auto-discovered by Index\Dashboard\Model::getModuleStats().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get board statistics for the dashboard widget.
     *
     * @return array
     */
    public static function getStats()
    {
        $row = static::createQuery()
            ->select(
                Sql::COUNT('id', 'total'),
                Sql::create("SUM(CASE WHEN `published`=1 THEN 1 ELSE 0 END) AS `published`"),
                Sql::create("SUM(CASE WHEN `published`=0 THEN 1 ELSE 0 END) AS `pending`"),
                Sql::SUM('comments', 'comments')
            )
            ->from('board_q')
            ->first();

        return [
            'total' => $row ? (int) $row->total : 0,
            'published' => $row ? (int) $row->published : 0,
            'pending' => $row ? (int) $row->pending : 0,
            'comments' => $row ? (int) $row->comments : 0
        ];
    }
}
