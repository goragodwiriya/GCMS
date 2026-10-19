<?php
/**
 * @filesource modules/board/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Category;

use Kotchasan\Language;

/**
 * Board Category Model
 *
 * Uses the shared `category` table with type='category'.
 * Fields topic, detail, icon are stored as JSON keyed by language code.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get decoded category config by category_id in a module.
     *
     * @param int $module_id
     * @param int $category_id
     *
     * @return array
     */
    public static function getConfigByCategoryId($module_id, $category_id)
    {
        if ($module_id <= 0 || $category_id <= 0) {
            return [];
        }

        $row = static::createQuery()
            ->select('config')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['category_id', $category_id],
                ['type', 'category']
            ])
            ->first();

        if (!$row) {
            return [];
        }

        return json_decode($row->config ?: '{}', true) ?: [];
    }

    /**
     * Merge module-level config with category-level override config.
     *
     * @param object|array $moduleConfig
     * @param int          $module_id
     * @param int          $category_id
     *
     * @return array
     */
    public static function getEffectiveConfig($moduleConfig, $module_id, $category_id)
    {
        $base = is_object($moduleConfig) ? (array) $moduleConfig : (is_array($moduleConfig) ? $moduleConfig : []);
        $categoryConfig = self::getConfigByCategoryId((int) $module_id, (int) $category_id);

        if (empty($categoryConfig)) {
            return $base;
        }

        return array_replace($base, $categoryConfig);
    }

    /**
     * Return the raw icon paths array for a record.
     *
     * @param object $db
     * @param int    $id  Primary key
     *
     * @return array
     */
    public static function getRawIcons($db, $id)
    {
        $row = $db->first('category', ['id', $id], ['icon']);
        return json_decode($row->icon ?: '{}', true) ?: [];
    }

    /**
     * Get one category record for the edit form.
     * Returns an object with decoded topic/detail/icon arrays and icon URLs.
     *
     * @param int $id        Primary key; 0 = new record
     * @param int $module_id
     *
     * @return object
     */
    public static function get($id, $module_id)
    {
        $languages = [];
        $icons = [];
        foreach (Language::installedLanguage() as $lng => $label) {
            $languages[$lng] = '';
            $icons[$lng] = WEB_URL.'images/no-image.webp';
        }
        $languages = (object) $languages;

        if ($id === 0) {
            return (object) [
                'id' => 0,
                'module_id' => $module_id,
                'category_id' => self::getNext($module_id, 'category'),
                'published' => 1,
                'topic' => $languages,
                'detail' => $languages,
                'icon' => $icons,
                'config' => (object) []
            ];
        }

        $row = static::createQuery()
            ->select('id', 'module_id', 'category_id', 'topic', 'detail', 'icon', 'config', 'published')
            ->from('category')
            ->where([
                ['id', $id],
                ['module_id', $module_id],
                ['type', 'category']
            ])
            ->first();

        if ($row) {
            $row->topic = json_decode($row->topic ?: '{}', true) ?: $languages;
            $row->detail = json_decode($row->detail ?: '{}', true) ?: $languages;
            $iconPaths = json_decode($row->icon ?: '{}', true) ?: $icons;
            $row->config = json_decode($row->config ?: '{}', true) ?: [];

            // Convert stored relative paths to full URLs for image preview
            $iconUrls = [];
            foreach ($iconPaths as $lng => $path) {
                $iconUrls[$lng] = ($path && file_exists(ROOT_PATH.$path))
                    ? WEB_URL.$path
                    : '';
            }
            $row->icon = $iconUrls ?: (object) [];
        }

        return $row;
    }

    /**
     * Insert or update one category record.
     *
     * @param object $db
     * @param int    $id        Primary key; 0 = new record
     * @param int    $module_id
     * @param array  $data      Columns to write (topic/detail/icon already JSON-encoded)
     *
     * @return int  Primary key of the saved record
     */
    public static function save($db, $id, $module_id, array $data)
    {
        if ($id > 0) {
            $db->update('category', ['id', $id], $data);
            return $id;
        }

        $data['module_id'] = $module_id;
        $data['type'] = 'category';
        return $db->insert('category', $data);
    }

    /**
     * Get the next category_id sequence number for a given module_id and type.
     *
     * @param int    $module_id
     * @param string $type
     *
     * @return int  Next category_id (max existing + 1) or 1 if none exist
     */
    public static function getNext($module_id, $type)
    {
        $maxRow = static::createQuery()
            ->select('MAX(category_id) AS max_id')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['type', $type]
            ])
            ->first();
        return ($maxRow && $maxRow->max_id) ? (int) $maxRow->max_id + 1 : 1;
    }

    /**
     * Delete one category record and its icon files.
     *
     * @param int $id  Primary key
     *
     * @return void
     */
    public static function remove($id)
    {
        $row = static::createQuery()
            ->select('icon')
            ->from('category')
            ->where(['id', $id])
            ->first();

        if ($row) {
            $icons = json_decode($row->icon ?: '{}', true) ?: [];
            foreach ($icons as $path) {
                if ($path && file_exists(ROOT_PATH.$path)) {
                    @unlink(ROOT_PATH.$path);
                }
            }
        }

        $db = \Kotchasan\DB::create();
        $db->delete('category', ['id', $id], 0);
    }

    /**
     * Get category options for a <select> element.
     *
     * @param int  $module_id
     *
     * @return array  [['value' => category_id, 'text' => topic], ...]
     */
    public static function categories($module_id)
    {
        $lng = Language::name();

        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['type', 'category'],
                ['published', 1]
            ])
            ->orderBy('category_id', 'ASC')
            ->cacheOn();

        $result = [];
        foreach ($query->fetchAll() as $item) {
            $topic = json_decode($item->topic, true) ?: [];
            $result[$item->category_id] = $topic[$lng] ?? '';
        }
        return $result;
    }

    /**
     * Get category options for a <select> element.
     *
     * @param int  $module_id
     * @param bool $includeUncategorized Whether to include an "Uncategorized" option with empty value
     *
     * @return array  [['value' => category_id, 'text' => topic], ...]
     */
    public static function toOptions($module_id, $includeUncategorized = false)
    {
        $categories = static::categories($module_id);
        if ($includeUncategorized) {
            $categories = ['' => 'Uncategorized'] + $categories;
        }
        return \Gcms\Controller::arrayToOptions($categories);
    }
}
