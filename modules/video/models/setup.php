<?php
/**
 * @filesource modules/video/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Setup;

/**
 * Video DataTable Model — mirrors Portfolio\Setup\Model's shape.
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
            ->select('id', 'module_id', 'youtube', 'topic', 'description', 'views', 'last_update')
            ->from('video')
            ->where(['module_id', $params['module_id']]);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['topic', 'LIKE', $search],
                ['description', 'LIKE', $search],
                ['youtube', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }
}
