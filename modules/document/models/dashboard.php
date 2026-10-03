<?php
/**
 * @filesource modules/document/models/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Dashboard;

use Kotchasan\Database\Sql;

/**
 * Document Dashboard Model
 *
 * Provides document statistics for the main dashboard.
 * This file is auto-discovered by Index\Dashboard\Model::getModuleStats().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get document statistics for the dashboard widget.
     *
     * @return array
     */
    public static function getStats()
    {
        $row = static::createQuery()
            ->select(
                Sql::COUNT('I.id', 'total'),
                Sql::create("SUM(CASE WHEN `I`.`published`=1 THEN 1 ELSE 0 END) AS `published`"),
                Sql::create("SUM(CASE WHEN `I`.`published`=0 THEN 1 ELSE 0 END) AS `unpublished`")
            )
            ->from('index I')
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
            ->first();

        return [
            'total' => $row ? (int) $row->total : 0,
            'published' => $row ? (int) $row->published : 0,
            'unpublished' => $row ? (int) $row->unpublished : 0
        ];
    }
}
