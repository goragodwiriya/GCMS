<?php
/**
 * @filesource modules/index/models/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Dashboard;

use Kotchasan\Database\Sql;
use Kotchasan\Language;

/**
 * Dashboard Model
 *
 * Aggregates core site metrics and delegates module-specific stats
 * to each module's own dashboard model (modules/{name}/models/dashboard.php).
 * Removing a module requires no code changes here.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get complete Dashboard data
     *
     * @return array
     */
    public static function get()
    {
        return [
            'core' => static::getCoreStats(),
            'modules' => static::getModuleStats(),
            'system' => static::getSystemStats()
        ];
    }

    /**
     * Get core site statistics: visitors and members
     *
     * @return array
     */
    public static function getCoreStats()
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $prevMonthStart = date('Y-m-01', strtotime('-1 month'));
        $prevMonthEnd = date('Y-m-t', strtotime('-1 month'));

        // ── Visitor stats from counter table ─────────────────────────────
        $todayRow = static::createQuery()
            ->select('visited', 'counter')
            ->from('counter')
            ->where(['date', $today])
            ->first();

        $visitorsToday = $todayRow ? (int) $todayRow->counter : 0;
        $pageviewsToday = $todayRow ? (int) $todayRow->visited : 0;

        $monthRow = static::createQuery()
            ->select(
                Sql::SUM('counter', 'unique_visitors'),
                Sql::SUM('visited', 'pageviews')
            )
            ->from('counter')
            ->where([
                ['date', '>=', $monthStart],
                ['date', '<=', $today]
            ])
            ->first();

        $uniqueVisitorsMonth = $monthRow ? (int) $monthRow->unique_visitors : 0;
        $pageviewsMonth = $monthRow ? (int) $monthRow->pageviews : 0;

        $totalRow = static::createQuery()
            ->select(Sql::SUM('visited', 'total'))
            ->from('counter')
            ->first();
        $totalPageviews = $totalRow ? (int) $totalRow->total : 0;

        // ── Member stats from user table ──────────────────────────────────
        $membersTotal = static::createQuery()
            ->selectCount()
            ->from('user')
            ->first();
        $membersTotal = $membersTotal ? (int) $membersTotal->count : 0;

        $membersActive = static::createQuery()
            ->selectCount()
            ->from('user')
            ->where(['active', 1])
            ->first();
        $membersActive = $membersActive ? (int) $membersActive->count : 0;

        $newThisMonth = static::createQuery()
            ->selectCount()
            ->from('user')
            ->where([
                ['created_at', '>=', $monthStart],
                ['created_at', '<=', $today]
            ])
            ->first();
        $newThisMonth = $newThisMonth ? (int) $newThisMonth->count : 0;

        $newLastMonth = static::createQuery()
            ->selectCount()
            ->from('user')
            ->where([
                ['created_at', '>=', $prevMonthStart],
                ['created_at', '<=', $prevMonthEnd]
            ])
            ->first();
        $newLastMonth = $newLastMonth ? (int) $newLastMonth->count : 0;

        $memberGrowth = $newLastMonth > 0
            ? round((($newThisMonth - $newLastMonth) / $newLastMonth) * 100, 1)
            : ($newThisMonth > 0 ? 100 : 0);

        return [
            'visitors_today' => $visitorsToday,
            'pageviews_today' => $pageviewsToday,
            'unique_visitors_month' => $uniqueVisitorsMonth,
            'pageviews_month' => $pageviewsMonth,
            'total_pageviews' => $totalPageviews,
            'members_total' => $membersTotal,
            'members_active' => $membersActive,
            'new_members_this_month' => $newThisMonth,
            'new_members_last_month' => $newLastMonth,
            'member_growth' => $memberGrowth
        ];
    }

    /**
     * Discover installed modules and collect their dashboard stats.
     * Each module may optionally provide modules/{name}/models/dashboard.php
     * with a static Model::getStats() method.
     * Removing a module requires no changes here.
     *
     * @return array  Keyed by module name
     */
    public static function getModuleStats()
    {
        $stats = [];
        $moduleNames = ['board', 'document', 'gallery', 'personnel'];

        foreach ($moduleNames as $name) {
            $file = ROOT_PATH.'modules/'.$name.'/models/dashboard.php';
            if (!is_file($file)) {
                continue;
            }
            $class = ucfirst($name).'\\Dashboard\\Model';
            if (!class_exists($class, false)) {
                require_once $file;
            }
            if (is_callable([$class, 'getStats'])) {
                $stats[$name] = $class::getStats();
            }
        }

        return $stats;
    }

    /**
     * Get the most recent activity log entries.
     *
     * @param int $limit
     *
     * @return array
     */
    public static function getRecentLogs($limit = 15)
    {
        $rows = static::createQuery()
            ->select('O.id', 'O.action', 'O.module', 'O.topic', 'O.reason', 'O.created_at', 'U.name')
            ->from('logs O')
            ->join('user U', ['U.id', 'O.member_id'], 'LEFT')
            ->orderBy('O.id', 'DESC')
            ->limit($limit)
            ->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) $row->id,
                'action' => $row->action,
                'module' => $row->module,
                // Legacy rows store "{LNG_…}" markers; TableManager never
                // translates row data, so resolve them here.
                'topic' => Language::trans((string) $row->topic),
                'reason' => $row->reason === null ? null : Language::trans((string) $row->reason),
                'created_at' => $row->created_at,
                'name' => $row->name ?? ''
            ];
        }

        return $result;
    }

    /**
     * Get system health information (folder sizes).
     *
     * @return array
     */
    public static function getSystemStats()
    {
        $dataPath = ROOT_PATH.DATA_FOLDER;
        $cachePath = $dataPath.'cache/';
        $logPath = $dataPath.'logs/';

        return [
            'cache_size' => static::folderSizeHuman($cachePath),
            'log_size' => static::folderSizeHuman($logPath),
            'data_size' => static::folderSizeHuman($dataPath)
        ];
    }

    /**
     * Calculate total size of a directory and return as human-readable string.
     *
     * @param string $path
     *
     * @return string  e.g. "2.5 MB"
     */
    private static function folderSizeHuman($path)
    {
        $bytes = 0;
        if (!is_dir($path)) {
            return '0 B';
        }
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iter as $file) {
            $bytes += $file->getSize();
        }
        if ($bytes < 1024) {
            return $bytes.' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1073741824, 2).' GB';
    }
}
