<?php
/**
 * @filesource modules/event/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Setup;

/**
 * Event DataTable Model — mirrors Portfolio\Setup\Model's shape.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $query = static::createQuery()
            ->select('A.id', 'A.module_id', 'A.topic', 'A.color', 'A.begin_date', 'A.end_date', 'A.published', 'A.last_update', 'U.name AS writer')
            ->from('event A')
            ->join('user U', ['U.id', 'A.member_id'], 'LEFT')
            ->where(['A.module_id', $params['module_id']]);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['A.topic', 'LIKE', $search],
                ['A.description', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }
}
