<?php
/**
 * @filesource widgets/personnel/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Personnel\Models;

/**
 * Widget Personnel Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index
{
    /**
     * Get published personnel for the widget
     *
     * @param int    $module_id
     * @param string $department empty = all departments
     * @param int    $level
     *
     * @return array
     */
    public static function getWidget($module_id, $department = '', $level = 1)
    {
        $where = [
            ['module_id', $module_id],
            ['published', 1]
        ];
        if (!empty($department)) {
            $where[] = ['department', $department];
        }
        if (!empty($level)) {
            $where[] = ['level', $level];
        }
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'name', 'department', 'position', 'phone', 'level', 'picture')
            ->from('personnel')
            ->where($where)
            ->orderBy('department')
            ->orderBy('level')
            ->cacheOn();

        return $query->fetchAll();
    }
}
