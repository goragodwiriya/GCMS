<?php
/**
 * @filesource modules/document/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Setup;

use Kotchasan\Database\Sql;

/**
 * Documents List Model — DataTable queries
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Build the DataTable query for articles
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [
            ['D.module_id', $params['module_id']],
            ['I.index', 0]
        ];
        if (!empty($params['category_id'])) {
            $where[] = ['I.category_id', $params['category_id']];
        }
        if ($params['published'] !== '') {
            $where[] = ['I.published', $params['published']];
        }
        $query = static::createQuery()
            ->select(
                'I.id',
                'D.module_id',
                'M.module',
                Sql::CONCAT(['D.topic'], 'topic', '<br>'),
                'I.picture',
                'I.category_id',
                'I.published',
                'I.published_date',
                'D.language',
                'I.updated_at',
                'I.visited'
            )
            ->from('index_detail D')
            ->join('index I', [['I.id', 'D.id'], ['I.module_id', 'D.module_id']])
            ->join('modules M', ['M.id', 'I.module_id'])
            ->where($where)
            ->groupBy('I.id');

        // Search
        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['D.topic', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Delete articles by IDs
     * Removes from index, index_detail, modules, document tables
     *
     * @param array $ids
     *
     * @return int Number deleted
     */
    public static function remove($ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $items = \Kotchasan\Model::createQuery()
            ->select('I.id', 'I.module_id')
            ->from('index_detail D')
            ->join('index I', [['I.id', 'D.id'], ['I.index', 0]])
            ->join('modules M', [['M.id', 'I.module_id'], ['M.owner', 'document']])
            ->where(['D.id', $ids])
            ->fetchAll();

        $db = \Kotchasan\DB::create();
        $removed = 0;

        foreach ($items as $item) {
            $removed++;
            $db->delete('index_detail', ['id', $item->id], 0);
            $db->delete('index', [['id', $item->id], ['module_id', $item->module_id], ['index', 0]], 0);
        }

        return $removed;
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
            $db->update('index', ['id', $id], [$column => $value]);
        }

        return true;
    }
}
