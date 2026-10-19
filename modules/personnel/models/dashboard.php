<?php
/**
 * @filesource modules/personnel/models/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Dashboard;

use Kotchasan\Database\Sql;

/**
 * Personnel Dashboard Model
 *
 * Provides personnel statistics for the main dashboard.
 * This file is auto-discovered by Index\Dashboard\Model::getModuleStats().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get personnel statistics for the dashboard widget.
     *
     * @return array
     */
    public static function getStats()
    {
        $total = static::createQuery()
            ->selectCount()
            ->from('personnel')
            ->where(['published', 1])
            ->first();

        $departments = static::createQuery()
            ->select('department', Sql::COUNT('id', 'count'))
            ->from('personnel')
            ->where(['published', 1])
            ->groupBy('department')
            ->orderBy('count', 'DESC')
            ->fetchAll();

        $deptList = [];
        foreach ($departments as $row) {
            $deptList[] = [
                'name' => $row->department,
                'count' => (int) $row->count
            ];
        }

        return [
            'total' => $total ? (int) $total->count : 0,
            'departments' => $deptList
        ];
    }
}
