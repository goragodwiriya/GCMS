<?php
/**
 * @filesource modules/personnel/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Category;

use Kotchasan;
use Kotchasan\Language;

/**
 * API Category Model
 *
 * Handles category table operations
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var array
     */
    public static $languages = ['th', 'en'];

    /**
     * Get all category for editing
     *
     * @param int $module_id
     * @param string $type
     *
     * @return array
     */
    public static function get($module_id, $type)
    {
        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['type', $type]
            ])
            ->orderBy('category_id', 'ASC');
        $data = [];
        foreach ($query->fetchAll() as $item) {
            $topic = json_decode($item->topic, true);
            if (!isset($data[$item->category_id])) {
                $data[$item->category_id]['id'] = $item->category_id;
                foreach (self::$languages as $lng) {
                    $data[$item->category_id][$lng] = $topic[$lng] ?? '';
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
     * Save category data
     *
     * @param int $module_id
     * @param string $type
     * @param array $save
     *
     * @return void
     */
    public static function save($module_id, $type, $save)
    {
        $db = Kotchasan\DB::create();

        $db->delete('category', [['module_id', $module_id], ['type', $type]], 0);

        foreach ($save as $category_id => $topic) {
            $db->insert('category', [
                'type' => $type,
                'module_id' => $module_id,
                'category_id' => $category_id,
                'topic' => $topic
            ]);
        }
    }

    /**
     * Get columns for table
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
     * @param int $module_id
     * @param string $type
     *
     * @return array
     */
    public static function toOptions($module_id, $type)
    {
        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['type', $type]
            ])
            ->orderBy('category_id', 'ASC')
            ->cacheOn();
        $lng = Language::name();
        $options = [];
        foreach ($query->fetchAll() as $item) {
            $topic = json_decode($item->topic, true);
            $options[] = ['value' => (string) $item->category_id, 'text' => $topic[$lng] ?? ''];
        }
        return $options;
    }

    /**
     * @return array
     */
    public static function levelOptions()
    {
        return [
            ['value' => 1, 'text' => '{LNG_Level} 1'],
            ['value' => 2, 'text' => '{LNG_Level} 2'],
            ['value' => 3, 'text' => '{LNG_Level} 3'],
            ['value' => 4, 'text' => '{LNG_Level} 4']
        ];
    }
}
