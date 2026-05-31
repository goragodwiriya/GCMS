<?php
/**
 * @filesource modules/index/models/counter.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Counter;

use Kotchasan\Http\Request;

/**
 * Visit Counter
 *
 * Tracks daily unique visitors and total page views, storing
 * aggregated data in the `counter` table.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * Record a page visit and update daily counters.
     *
     * - Detects a new calendar day and resets per-page `visited_today` counts.
     * - Inserts or updates the daily row in the `counter` table:
     *   - `visited`    — total page hits for the day (always incremented)
     *   - `pages_view` — alias for total page views (always incremented)
     *   - `counter`    — unique visitors for the day (incremented once per session per day)
     *   - `time`       — Unix timestamp of the last recorded hit
     *
     * @param Request $request
     *
     * @return bool TRUE on the first call of a new calendar day, FALSE otherwise
     */
    public static function init(Request $request)
    {
        if (!defined('MAIN_INIT')) {
            return false;
        }

        $today = date('Y-m-d');
        $d = (int) date('d');

        // ── New-day detection via lightweight flat file ───────────────────
        $counter_file = ROOT_PATH.DATA_FOLDER.'counter.log';
        $is_new_day = false;

        $c = is_file($counter_file) ? (int) file_get_contents($counter_file) : 0;

        // Database
        $db = \Kotchasan\DB::create();

        if ($d !== $c) {
            $f = @fopen($counter_file, 'wb');
            if ($f) {
                fwrite($f, date('d-m-Y H:i:s'));
                fclose($f);
            }
            // Reset per-page daily view counts across all index items
            $db->update('index', [], ['visited_today' => 0]);
            $is_new_day = true;
        }

        // ── Unique-visitor detection via session ──────────────────────────
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $is_unique = (($_SESSION['counter_date'] ?? '') !== $today);
        if ($is_unique) {
            $_SESSION['counter_date'] = $today;
        }

        // ── Upsert daily counter row ──────────────────────────────────────

        $row = $db->first('counter', ['date', $today], ['id']);

        if ($row) {
            // Atomically increment page-view columns
            $db->increment('counter', ['date', $today], ['visited', 'pages_view']);
            // Increment unique-visitor counter only once per session per day
            if ($is_unique) {
                $db->increment('counter', ['date', $today], 'counter');
            }
            // Update the last-hit timestamp
            $db->update('counter', ['date', $today], ['time' => time()]);
        } else {
            // First hit of the day — insert a fresh row
            $db->insert('counter', [
                'date' => $today,
                'counter' => $is_unique ? 1 : 0,
                'visited' => 1,
                'pages_view' => 1,
                'time' => time()
            ]);
        }

        return $is_new_day;
    }
}
