<?php
/**
 * @filesource modules/index/models/adminpages.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\AdminPages;

use Kotchasan\Language;

/**
 * API Admin Pages Model
 *
 * Handles page table operations
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query data to send to DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $query = static::createQuery()
            ->select(
                'D.id',
                'D.module_id',
                'D.topic',
                'I.published',
                'I.published_date',
                'D.language',
                'M.module',
                'I.updated_at',
                'I.visited'
            )
            ->from('index I')
            ->join('modules M', [['M.id', 'I.module_id']])
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
            ->where([
                ['M.owner', 'index'],
                ['I.index', 1]
            ]);

        // Search (OR across topic / module)
        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['D.topic', 'LIKE', $search],
                ['M.module', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Delete page by ID
     * Removes records from index_detail and index tables,
     * and unlinks any menus that pointed to this page.
     *
     * @param int|array $ids Page ID or array of IDs
     *
     * @return int Number of deleted index records (0 if not found)
     */
    public static function remove($ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $search = \Kotchasan\Model::createQuery()
            ->select('D.id', 'D.module_id', 'D.language')
            ->from('index_detail D')
            ->join('index_detail T', ['T.module_id', 'D.module_id'])
            ->join('index I', ['I.module_id', 'T.module_id'])
            ->where([
                ['I.index', 1],
                ['T.id', $ids]
            ])
            ->fetchAll();
        $db = \Kotchasan\DB::create();
        $removed = 0;
        foreach ($search as $item) {
            $removed++;
            $db->delete('index', ['id', $item->id]);
            $db->delete('index_detail', [['id', $item->id], ['language', $item->language]]);
        }
        return $removed;
    }

    /**
     * Update status column for given page IDs
     *
     * @param int|array $ids Page ID or array of IDs
     * @param string $column
     * @param mixed $value
     *
     * @return int
     */
    public static function updateStatus($ids, $column, $value)
    {
        if (empty($ids)) {
            return 0;
        }

        return \Kotchasan\DB::create()->update('index', ['id', $ids], [$column => $value]);
    }
}
