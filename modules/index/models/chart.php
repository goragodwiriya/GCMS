<?php
/**
 * @filesource modules/index/models/chart.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Chart;

use Kotchasan\Database\Sql;
use Kotchasan\Date;

/**
 * Chart Model
 *
 * Provides data series for dashboard charts.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * daily visitor and page-view trend.
     *
     * Returns GraphComponent series format:
     * [
     *   {"name": "Visitors",   "data": [{"label": "2026-03-01", "value": 100}, ...]},
     *   {"name": "Page Views", "data": [{"label": "2026-03-01", "value": 200}, ...]}
     * ]
     *
     * @return array
     */
    public static function visitors()
    {
        $select = [
            Sql::MONTH('date', 'month'),
            Sql::YEAR('date', 'year'),
            Sql::SUM('pages_view', 'pages_view'),
            Sql::SUM('visited', 'visited')
        ];
        $sql1 = static::createQuery()
            ->select($select)
            ->from('counter')
            ->groupBy(['year', 'month'])
            ->orderBy('year', 'DESC')
            ->orderBy('month', 'DESC')
            ->limit(12);
        $query = static::createQuery()
            ->select()
            ->from([$sql1, 'A'])
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->cacheOn();
        $visitorsSeries = [];
        $pageviewsSeries = [];
        foreach ($query->fetchAll() as $row) {
            $visitorsSeries[] = ['label' => Date::monthName($row->month), 'value' => $row->visited];
            $pageviewsSeries[] = ['label' => Date::monthName($row->month), 'value' => $row->pages_view];
        }
        return [
            ['name' => '{LNG_Visitors}', 'data' => $visitorsSeries],
            ['name' => '{LNG_Page Views}', 'data' => $pageviewsSeries]
        ];
    }

    /**
     * Content distribution across installed modules.
     *
     * Returns GraphComponent simple array format (single series / pie chart):
     * [
     *   {"label": "Board",     "value": 100},
     *   {"label": "Documents", "value": 50},
     *   {"label": "Gallery",   "value": 10},
     *   {"label": "Personnel", "value": 30}
     * ]
     *
     * @return array
     */
    public static function content()
    {
        $result = [];

        $modules = [
            'board' => ['file' => 'board/models/dashboard.php', 'class' => 'Board\\Dashboard\\Model', 'key' => 'total', 'label' => '{LNG_Board}'],
            'document' => ['file' => 'document/models/dashboard.php', 'class' => 'Document\\Dashboard\\Model', 'key' => 'total', 'label' => '{LNG_Documents}'],
            'gallery' => ['file' => 'gallery/models/dashboard.php', 'class' => 'Gallery\\Dashboard\\Model', 'key' => 'albums', 'label' => '{LNG_Gallery}'],
            'personnel' => ['file' => 'personnel/models/dashboard.php', 'class' => 'Personnel\\Dashboard\\Model', 'key' => 'total', 'label' => '{LNG_Personnel}']
        ];

        foreach ($modules as $cfg) {
            $file = ROOT_PATH.'modules/'.$cfg['file'];
            if (!is_file($file)) {
                continue;
            }
            $class = $cfg['class'];
            if (!class_exists($class, false)) {
                require_once $file;
            }
            if (is_callable([$class, 'getStats'])) {
                $stats = $class::getStats();
                $result[] = ['label' => $cfg['label'], 'value' => $stats[$cfg['key']] ?? 0];
            }
        }

        return [
            ['name' => '{LNG_Content Distribution}', 'data' => $result]
        ];
    }
}
