<?php
/**
 * @filesource widgets/textlinks/models/table.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlinks\Models;

use Kotchasan\Database\Sql;

/**
 * Textlinks Widget — Table Model
 *
 * Database queries for the Textlinks widget table.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Table extends \Kotchasan\Model
{
    /**
     * Return the base query for the DataTable.
     *
     * Searched columns : topic, isbn, product_no
     * Filterable       : published
     *
     * @param array $params  Parsed by \Gcms\Table::parseParams() + getCustomParams()
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params)
    {
        $where = [];
        if (!empty($params['name'])) {
            $where[] = ['name', $params['name']];
        }
        return static::createQuery()
            ->select(
                'id',
                'name',
                'description',
                'published',
                'type',
                'url',
                'text',
                'width',
                'height',
                'publish_start',
                'publish_end',
                'link_order'
            )
            ->from('textlink')
            ->where($where);
    }

    /**
     * Delete textlinks and their associated files.
     *
     * @param array $ids  Textlink IDs to delete
     *
     * @return int  Number of items deleted
     */
    public static function remove(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $links = static::createQuery()
            ->select('id', 'logo')
            ->from('textlink')
            ->where(['id', $ids])
            ->fetchAll();

        if (empty($links)) {
            return 0;
        }

        // Delete associated logo files
        $dir = ROOT_PATH.DATA_FOLDER.'textlink/';
        foreach ($links as $link) {
            $filePath = $dir.$link->logo;
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
            }
        }

        // Remove textlink rows
        \Kotchasan\DB::create()->delete('textlink', ['id', $ids], 0);

        // Return the count of deleted items
        return count($ids);
    }

    /**
     * Update a status column for a set of product IDs.
     *
     * @param array  $ids     Product IDs to update
     * @param string $column  Column name (e.g. 'published')
     * @param mixed  $value   New value
     *
     * @return int  Rows affected
     */
    public static function updateStatus(array $ids, string $column, $value): int
    {
        if (empty($ids)) {
            return 0;
        }

        return \Kotchasan\DB::create()->update('textlink', ['id', $ids], [$column => $value]);
    }

    /**
     * Persist a new row order to the link_order column.
     *
     * @param array $order  Array of ['id' => int, 'position' => int] from the JS table widget
     *
     * @return void
     */
    public static function reorder(array $order): void
    {
        if (empty($order)) {
            return;
        }

        $db = \Kotchasan\DB::create();

        foreach ($order as $item) {
            $id = isset($item['id']) ? (int) $item['id'] : 0;
            $position = isset($item['position']) ? (int) $item['position'] : 0;

            if ($id > 0) {
                $db->update('textlink', ['id', $id], ['link_order' => $position]);
            }
        }
    }

    /**
     * Fetch distinct textlink names for filter options.
     *
     * @return array  Array of ['value' => name, 'text' => name] for each distinct textlink name
     */
    public static function allName()
    {
        $query = static::createQuery()
            ->select(Sql::DISTINCT('name'))
            ->from('textlink')
            ->orderBy('name');

        $options = [];
        foreach ($query->fetchAll() as $row) {
            $options[] = ['value' => $row->name, 'text' => $row->name];
        }
        return $options;
    }
}
