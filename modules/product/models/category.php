<?php
/**
 * @filesource modules/product/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Category;

use Kotchasan;
use Kotchasan\Language;

/**
 * Product Category Model
 *
 * Stored in the shared {prefix}_category table with type = 'category' — the
 * same type the legacy (GCMS 11–14) product module and document/board/download
 * use, so upgraded sites keep their categories and Web\Category works as-is.
 * Legacy rows keep a single topic under the '' (all languages) key.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var string
     */
    public static $type = 'category';

    /**
     * @var array
     */
    public static $languages = ['th', 'en'];

    /**
     * Get all categories for editing
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function get($module_id)
    {
        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['type', self::$type]
            ])
            ->orderBy('category_id', 'ASC');
        $data = [];
        foreach ($query->fetchAll() as $item) {
            $topic = json_decode((string) $item->topic, true);
            if (!is_array($topic)) {
                $topic = [];
            }
            if (!isset($data[$item->category_id])) {
                $data[$item->category_id]['id'] = $item->category_id;
                foreach (self::$languages as $lng) {
                    $data[$item->category_id][$lng] = $topic[$lng] ?? $topic[''] ?? '';
                }
            }
        }
        if (empty($data)) {
            $data[0] = ['id' => 1];
            foreach (self::$languages as $lng) {
                $data[0][$lng] = '';
            }
        }
        return array_values($data);
    }

    /**
     * Save category data (delete then insert)
     *
     * @param int   $module_id
     * @param array $save  [category_id => json topic, ...]
     *
     * @return void
     */
    public static function save($module_id, $save)
    {
        $db = Kotchasan\DB::create();
        $db->delete('category', [['module_id', $module_id], ['type', self::$type]], 0);
        // mediumtext NOT NULL columns without a default (and the legacy
        // group_id/c1/c2 on upgraded sites) reject an INSERT that omits them
        // under STRICT_TRANS_TABLES — fill whichever of them the table has.
        $defaults = array_intersect_key(
            ['config' => '', 'detail' => '', 'icon' => '', 'published' => 1, 'group_id' => 0, 'c1' => 0, 'c2' => 0],
            array_flip(static::tableColumns('category'))
        );
        foreach ($save as $category_id => $topic) {
            $db->insert('category', [
                'type' => self::$type,
                'module_id' => $module_id,
                'category_id' => $category_id,
                'topic' => $topic
            ] + $defaults);
        }
    }

    /**
     * Get columns for the category editor table
     *
     * @return array
     */
    public static function getColumns()
    {
        $columns = [
            [
                'field' => 'id',
                'label' => 'ID',
                'cellElement' => 'text',
                'size' => 5
            ]
        ];
        foreach (self::$languages as $key) {
            $columns[] = [
                'field' => $key,
                'label' => ucfirst($key),
                'cellElement' => 'text',
                'size' => 20
            ];
        }
        return $columns;
    }

    /**
     * Categories as <option> list for the current language
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function toOptions($module_id)
    {
        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['type', self::$type]
            ])
            ->orderBy('category_id', 'ASC')
            ->cacheOn();
        $lng = Language::name();
        $options = [];
        foreach ($query->fetchAll() as $item) {
            $topic = json_decode((string) $item->topic, true);
            $options[] = [
                'value' => (string) $item->category_id,
                'text' => is_array($topic) ? ($topic[$lng] ?? $topic[''] ?? reset($topic) ?: '') : ''
            ];
        }
        return $options;
    }
}
