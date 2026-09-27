<?php
/**
 * @filesource widgets/textlinks/models/settings.php
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
 * Database queries for the Textlinks widget settings.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\Model
{
    /**
     * Insert or update a textlink row.
     *
     * @param \Kotchasan\DB $db      Active DB instance
     * @param array         $data    Column → value map; must include 'id'
     * @param bool          $isNew   true = INSERT, false = UPDATE
     *
     * @return void
     */
    public static function save(\Kotchasan\DB $db, array $data, bool $isNew): void
    {
        if ($isNew) {
            $db->insert('textlink', $data);
        } else {
            $id = $data['id'];
            unset($data['id']);
            $db->update('textlink', ['id', $id], $data);
        }
    }

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
    public static function get($id)
    {
        if ($id > 0) {
            return static::createQuery()
                ->select()
                ->from('textlink')
                ->where(['id', $id])
                ->first();
        }
        return (object) [
            'id' => 0,
            'name' => '',
            'publish_start' => date('Y-m-d'),
            'publish_end' => null
        ];
    }

    /**
     * Delete products and their associated files.
     *
     * @param array $ids  Product IDs to delete
     *
     * @return int  Number of items deleted
     */
    public static function remove(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        // 1. Remove linked details rows
        static::createQuery()
            ->delete('product_details')
            ->where([['product_id', $ids]])
            ->execute();

        // 2. Remove product rows
        static::createQuery()
            ->delete('product')
            ->where([['id', $ids]])
            ->execute();

        // 3. Remove image files
        $dir = ROOT_PATH.DATA_FOLDER.'product/';

        foreach ($ids as $id) {
            $file = $dir.'*-'.$id.self::$cfg->stored_img_type;
            foreach (glob($file) ?: [] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

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
     * @param array $order  Array of ['id' => int, 'position' => int] from the JS settings widget
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
