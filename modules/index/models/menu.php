<?php
/**
 * @filesource modules/index/models/menu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menu;

use Kotchasan\Language;

/**
 * Class for loading menu items from the GCMS database
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get menu by ID
     * $id = 0 return new
     *
     * @param int $id
     * @param string $parent
     *
     * @return object|null
     */
    public static function get($id, $parent = '')
    {
        if ($id === 0) {
            // New menu item, return default values
            return (object) [
                'id' => 0,
                'index_id' => 0,
                'parent' => $parent,
                'level' => 0,
                'language' => Language::name(),
                'menu_text' => '',
                'menu_tooltip' => '',
                'accesskey' => '',
                'menu_order' => 0,
                'menu_url' => '',
                'menu_target' => '',
                'alias' => '',
                'owner' => '',
                'module' => '',
                'published' => 1,
                'icon' => '',
                'type' => 1,
                'action' => 0
            ];
        }
        // Load existing menu item from database
        $menu = static::createQuery()
            ->select('U.*', 'M.module', 'M.owner', 'I.module_id', 'U.parent parent')
            ->from('menus U')
            ->join('index I', ['I.id', 'U.index_id'], 'LEFT')
            ->join('modules M', ['M.id', 'I.module_id'], 'LEFT')
            ->where(['U.id', $id])
            ->first();
        if ($menu) {
            // type
            if ($menu->menu_order == 1) {
                $menu->type = 0;
            } elseif ($menu->level == 0) {
                $menu->type = 1;
            } elseif ($menu->level == 1) {
                $menu->type = 2;
            } else {
                $menu->type = 3;
            }
            // action
            if ($menu->menu_url != '') {
                $menu->action = 2;
            } elseif ($menu->index_id == 0) {
                $menu->action = 0;
            } else {
                $menu->action = 1;
            }
            $menu->icon = $menu->icon ?? '';
        }
        return $menu;
    }

    /**
     * Load menus from the database
     *
     * @return array
     */
    public static function queryAllMenus()
    {
        $lng = [Language::name(), ''];
        $query = static::createQuery()
            ->select(
                'U.index_id',
                'U.parent parent',
                'U.level',
                'U.menu_text',
                'U.menu_tooltip',
                'U.accesskey',
                'U.menu_url',
                'U.menu_target',
                'U.alias',
                'U.published',
                'U.icon',
                'M.module module_name'
            )
            ->from('menus U')
            ->join('index I', ['I.id', 'U.index_id'], 'LEFT')
            ->join('modules M', ['M.id', 'I.module_id'], 'LEFT')
            ->where(['U.language', $lng])
            ->orderBy('U.parent')
            ->orderBy('U.menu_order')
            ->cacheOn();
        $result = [];
        foreach ($query->fetchAll() as $item) {
            $item->parent = preg_replace('/^([012]_)/', '', $item->parent);
            // Build module sub-object so createItem() can resolve URL and select state
            if (!empty($item->module_name) || $item->index_id > 0) {
                $item->module = (object) ['module' => (string) $item->module_name, 'index_id' => (int) $item->index_id];
            } else {
                $item->module = null;
            }
            $result[] = $item;
        }
        return $result;
    }

    /**
     * Get menu order options for a given parent position
     * Returns list of existing menus in that parent as [{value, text}]
     * used to populate the menu_order <select>
     *
     * @param string $parent  e.g. "0_MAINMENU", "1_SIDEMENU"
     *
     * @return array  [{value: int, text: string}, ...]
     */
    public static function getOrderOptions($parent)
    {
        $query = static::createQuery()
            ->select(['I.id', 'M.owner', 'M.module', 'D.topic', 'D.language'])
            ->from('index I')
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
            ->join('modules M', ['M.id', 'I.module_id'], 'INNER')
            ->where([
                ['I.index', 1],
                ['D.language', [Language::name(), '']]
            ])
            ->orderBy('M.owner')
            ->orderBy('M.module')
            ->orderBy('D.language');
        $result = [];
        $owner = '';
        foreach ($query->fetchAll() as $item) {
            if ($item->owner != $owner) {
                $owner = $item->owner;
                $result[] = ['value' => '', 'text' => "── {$owner} ──", 'disabled' => true];
            }
            $result[] = [
                'value' => $item->id,
                'text' => $item->module.(empty($item->language) ? '' : " [{$item->language}]").', '.$item->topic
            ];
        }
        return $result;
    }
}
