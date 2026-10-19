<?php
/**
 * @filesource modules/document/models/categories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Categories;

/**
 * Categories DataTable Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query for DataTable — returns rows from the shared `category` table
     *
     * @param array $params  ['module_id' => int, 'type' => string]
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        return static::createQuery()
            ->select('id', 'category_id', 'module_id', 'topic', 'detail', 'icon', 'config', 'published')
            ->from('category')
            ->where([
                ['type', 'category'],
                ['module_id', $params['module_id']]
            ]);
    }

    /**
     * Update status column for given IDs
     *
     * @param array  $ids
     * @param string $column
     * @param int    $value
     *
     * @return bool
     */
    public static function updateStatus($ids, $column, $value)
    {
        if (empty($ids)) {
            return false;
        }

        $db = \Kotchasan\DB::create();

        foreach ($ids as $id) {
            $db->update('category', ['id', $id], [$column => $value]);
        }

        return true;
    }
}
