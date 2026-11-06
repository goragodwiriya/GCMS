<?php
/**
 * @filesource Web/Category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Web;

use Kotchasan\Language;

/**
 * Class for managing category data
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Category
{
    /**
     * @var mixed
     */
    private $datas = null;

    /**
     * Constructor for loading category data from the database
     *
     * @param int $module_id
     */
    private function __construct($module_id)
    {
        $query = \Kotchasan\Model::createQuery()
            ->select('category_id', 'type', 'topic', 'detail', 'icon', 'config')
            ->from('category')
            ->where([
                ['module_id', $module_id],
                ['published', 1]
            ])
            ->orderBy('category_id')
            ->cacheOn();

        // Active language
        $lng = Language::name();

        $this->datas = [];
        foreach ($query->fetchAll() as $item) {
            $topic = json_decode($item->topic ?? '', true);
            $detail = json_decode($item->detail ?? '', true);
            $icon = json_decode($item->icon ?? '', true);
            $config = ($item->config && json_decode($item->config)) ?? new \stdClass();
            $this->datas[$item->type][$item->category_id] = (object) [
                'topic' => self::localized($topic, $lng),
                'detail' => self::localized($detail, $lng),
                'icon' => self::localized($icon, $lng),
                'config' => $config
            ];
        }
    }

    /**
     * Value of a {"th": ..., "en": ...} field for a language, falling back to
     * the '' (all languages) key legacy categories use, then to any other
     * language — a category named in one language only is still named.
     *
     * @param mixed  $values decoded JSON
     * @param string $lng
     *
     * @return string
     */
    private static function localized($values, $lng)
    {
        if (!is_array($values)) {
            return '';
        }
        foreach ([$lng, ''] as $key) {
            if (isset($values[$key]) && $values[$key] !== '') {
                return (string) $values[$key];
            }
        }
        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Static method for creating an instance of the Category class
     *
     * @param int $module_id
     */
    public static function create($module_id)
    {
        return new static($module_id);
    }

    /**
     * Check if category data is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->datas);
    }

    /**
     * Function for retrieving category information by ID
     *
     * @param string $type Type of category.
     * @param int $category_id ID of the category.
     *
     * @return object|null Category information, or null if not found.
     */
    public function get($type, $category_id)
    {
        return $this->datas[$type][$category_id] ?? null;
    }

    /**
     * Function for creating category selection lists
     *
     * @param string $type Type of category.

     * @return array Category selection list
     */
    public function toSelect($type)
    {
        if (empty($this->datas[$type])) {
            return [];
        }
        $result = [];
        foreach ($this->datas[$type] as $category_id => $item) {
            $result[$category_id] = $item->topic;
        }
        return $result;
    }

    /**
     * Function for retrieving all category information
     *
     * @param string $type Type of category.
     *
     * @return array all category data
     */
    public function all($type)
    {
        return empty($this->datas[$type]) ? [] : $this->datas[$type];
    }
}
