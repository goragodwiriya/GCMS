<?php
/**
 * @filesource modules/personnel/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Setup;

/**
 * Personnel DataTable Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query for DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = [
            ['module_id', $params['module_id']]
        ];
        if (!empty($params['department'])) {
            $where[] = ['department', $params['department']];
        }
        if (!empty($params['level'])) {
            $where[] = ['level', $params['level']];
        }

        $query = static::createQuery()
            ->select('id', 'module_id', 'name', 'department', 'level', 'position', 'phone', 'published', 'updated_at', 'picture')
            ->from('personnel')
            ->where($where);

        // Search (OR condition)
        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $where = [
                ['name', 'LIKE', $search],
                ['position', 'LIKE', $search],
                ['phone', 'LIKE', $search]
            ];

            $query->where($where, 'OR');
        }

        return $query;
    }
}
