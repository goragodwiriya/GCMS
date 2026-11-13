<?php
/**
 * @filesource modules/personnel/models/lists.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Lists;

/**
 * Personnel Lists Model – for public API / widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get published personnel, optionally filtered by department
     *
     * @param int    $module_id
     * @param string $department empty = all
     * @param int    $limit      0 = no limit
     *
     * @return array
     */
    public static function getPersonnel($module_id, $department = '', $limit = 0)
    {
        $where = [
            ['module_id', $module_id],
            ['published', 1]
        ];
        if (!empty($department)) {
            $where[] = ['department', $department];
        }
        $query = static::createQuery()
            ->select('id', 'name', 'department', 'position', 'phone', 'email', 'level', 'detail', 'picture')
            ->from('personnel')
            ->where($where)
            ->orderBy('department', 'ASC')
            ->orderBy('level', 'ASC')
            ->orderBy('name', 'ASC')
            ->cacheOn();
        if ($limit > 0) {
            $query->limit($limit);
        }
        return $query->fetchAll();
    }

    /**
     * Get single person
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function getPerson($id)
    {
        return static::createQuery()
            ->select()
            ->from('personnel')
            ->where([
                ['id', $id],
                ['published', 1]
            ])
            ->cacheOn()
            ->first();
    }

    /**
     * Get distinct departments for a module
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function getDepartments($module_id)
    {
        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['type', 'department']
            ])
            ->orderBy('category_id', 'ASC')
            ->cacheOn();
        $rows = [];
        foreach ($query->fetchAll() as $row) {
            $topic = json_decode($row->topic, true);
            $rows[(int) $row->category_id] = $topic[LANGUAGE] ?? $row->category_id;
        }
        return $rows;
    }

    /**
     * Get featured personnel for widget
     *
     * @param int $module_id
     * @param int $limit
     *
     * @return array
     */
    public static function getWidget($module_id, $limit = 6)
    {
        return static::createQuery()
            ->select('id', 'name', 'department', 'position')
            ->from('personnel')
            ->where([
                ['module_id', $module_id],
                ['published', 1]
            ])
            ->orderBy('department', 'ASC')
            ->orderBy('level', 'ASC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();
    }
}
