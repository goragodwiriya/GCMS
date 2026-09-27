<?php
/**
 * @filesource modules/download/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Category;

use Kotchasan\Language;

/**
 * Download Category Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Category type key in shared category table.
     *
     * @var string
     */
    public static $type = 'category';

    /**
     * Get language columns.
     *
     * @return array
     */
    public static function getLanguages()
    {
        return \Index\Language\Model::getLanguages();
    }

    /**
     * Get all categories for editable rows form.
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function get($module_id)
    {
        $languages = self::getLanguages();

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
                foreach ($languages as $lng) {
                    $data[$item->category_id][$lng] = $topic[$lng] ?? '';
                }
            }
        }

        if (empty($data)) {
            $data[0] = ['id' => 1];
            foreach ($languages as $lng) {
                $data[0][$lng] = '';
            }
        }

        return array_values($data);
    }

    /**
     * Save category rows (replace-all strategy).
     *
     * @param int    $module_id
     * @param string $type
     * @param array  $save [category_id => json topic]
     *
     * @return void
     */
    public static function save($module_id, $type, $save)
    {
        $db = \Kotchasan\DB::create();
        $db->delete('category', [['module_id', $module_id], ['type', $type]], 0);

        foreach ($save as $category_id => $topic) {
            $db->insert('category', [
                'type' => $type,
                'module_id' => $module_id,
                'category_id' => $category_id,
                'topic' => $topic,
                'published' => 1
            ]);
        }
    }

    /**
     * Columns schema for editable rows table.
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

        foreach (self::getLanguages() as $lng) {
            $columns[] = [
                'field' => $lng,
                'label' => ucfirst($lng),
                'cellElement' => 'text',
                'size' => 20
            ];
        }

        return $columns;
    }

    /**
     * Category options for selects (current language).
     *
     * @param int  $module_id
     * @param bool $includeUncategorized
     *
     * @return array
     */
    public static function toOptions($module_id, $includeUncategorized = false)
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
        if ($includeUncategorized) {
            $options[] = ['value' => '0', 'text' => '{LNG_Uncategorized}'];
        }

        foreach ($query->fetchAll() as $item) {
            $topic = json_decode((string) $item->topic, true);
            if (!is_array($topic)) {
                $topic = [];
            }
            $options[] = [
                'value' => (string) $item->category_id,
                'text' => $topic[$lng] ?? ''
            ];
        }

        return $options;
    }

    /**
     * Lookup map for category ID => topic.
     *
     * @param int $module_id
     *
     * @return array
     */
    public static function lookup($module_id)
    {
        $map = [];
        foreach (self::toOptions($module_id) as $opt) {
            $map[(int) $opt['value']] = $opt['text'];
        }

        return $map;
    }
}
