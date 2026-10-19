<?php
/**
 * @filesource modules/portfolio/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Setup;

/**
 * Portfolio DataTable Model — mirrors Personnel\Setup\Model's shape.
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
        $where = [
            ['module_id', $params['module_id']]
        ];

        $query = static::createQuery()
            ->select('id', 'module_id', 'title', 'image', 'url', 'published', 'created_at', 'visited')
            ->from('portfolio')
            ->where($where);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['title', 'LIKE', $search],
                ['keywords', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }
}
