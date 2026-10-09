<?php
/**
 * @filesource modules/index/models/adminmodules.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\AdminModules;

use Kotchasan\Language;

/**
 * API Admin Modules Model
 *
 * Handles module table operations
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
                'I.id',
                'M.id AS module_id',
                'D.topic',
                'I.published',
                'D.language',
                'M.module',
                'M.owner',
                'I.updated_at',
                'I.visited'
            )
            ->from('modules M')
            ->join('index_detail D', ['D.module_id', 'M.id'])
            ->join('index I', [['I.id', 'D.id'], ['I.module_id', 'M.id']])
            ->where([
                ['M.owner', '!=', 'index'],
                ['I.index', 1],
                ['D.language', ['', Language::name()]]
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
     * Delete module by ID
     * Removes records from index_detail and index tables,
     * and unlinks any menus that pointed to this module.
     *
     * @param array $ids Module IDs to delete
     *
     * @return int Number of deleted index records (0 if not found)
     */
    public static function remove($ids)
    {

        if (empty($ids)) {
            return 0;
        }

        $db = \Kotchasan\DB::create();

        // Remove all language variants of the page content
        $db->delete('index_detail', ['id', $ids], 0);

        // Remove the page record itself
        $removed = $db->delete('index', ['id', $ids], 0);

        // Unlink menus that referenced this page
        if ($removed > 0) {
            $db->update('menus', ['index_id', $ids], [
                'index_id' => 0,
                'menu_url' => '',
                'menu_target' => ''
            ]);
        }

        return $removed;
    }

    /**
     * Update status column for given page IDs
     *
     * @param array $ids
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
