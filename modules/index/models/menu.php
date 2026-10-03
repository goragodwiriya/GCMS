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
     * Cache whether menus.icon exists in the current tenant DB.
     *
     * @var bool|null
     */
    private static $hasMenuIconColumn = null;

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
                $menu->menu_url = str_replace('{WEB_URL}', WEB_URL, $menu->menu_url);
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
        $select = [
            'U.index_id',
            'U.parent parent',
            'U.level',
            'U.menu_text',
            'U.menu_tooltip',
            'U.accesskey',
            'U.menu_url',
            'U.menu_target',
            'U.alias',
            'U.published'
        ];
        if (self::hasMenuIconColumn()) {
            $select[] = 'U.icon';
        }
        $select[] = 'M.module module_name';

        $query = static::createQuery()
            ->select($select)
            ->from('menus U')
            ->join('index I', ['I.id', 'U.index_id'], 'LEFT')
            ->join('modules M', ['M.id', 'I.module_id'], 'LEFT')
            ->where([
                ['U.published', 1],
                ['U.language', $lng]
            ])
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
            if (!isset($item->icon)) {
                $item->icon = '';
            }
            $result[] = $item;
        }
        return $result;
    }

    /**
     * @return bool
     */
    private static function hasMenuIconColumn()
    {
        if (self::$hasMenuIconColumn === null) {
            self::$hasMenuIconColumn = \Kotchasan\DB::create()->fieldExists('menus', 'icon');
        }

        return self::$hasMenuIconColumn;
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
            ->join('modules M', ['M.id', 'I.module_id'])
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

        // System pages (Search/Login/Logout/Register/Forgot/Profile/Admin
        // area) and any additional targets installed modules register via
        // {Owner}\Admin\Init\Model::initMenuTargets() — same capability as
        // gcms241021's Gcms::$module_menus + initMenuwrite() hook, selected
        // from this same dropdown alongside real installed pages.
        $result[] = ['value' => '', 'text' => '── {LNG_System pages} ──', 'disabled' => true];
        foreach (self::virtualTargets() as $value => $target) {
            $result[] = ['value' => $value, 'text' => $target['text']];
        }

        return $result;
    }

    /**
     * Built-in virtual menu targets (not backed by an `index` row) plus any
     * additional targets installed modules register — mirrors
     * gcms241021's Gcms::$module_menus + initMenuwrite() hook. Values are
     * "sys:"/"{owner}:"-prefixed strings (never a plain integer, so
     * Adminmenu\Controller::save() can tell them apart from a real
     * `index.id`) resolved back to a URL by resolveVirtualTarget().
     *
     * A module opts in by defining, in modules/{owner}/models/admin/init.php:
     *   namespace Owner\Admin\Init;
     *   class Model {
     *       public static function initMenuTargets() {
     *           return ['report' => ['text' => 'Voting results', 'url' => '...']];
     *       }
     *   }
     * which becomes selectable as value "owner:report".
     *
     * @return array<string, array{text: string, url: string}>
     */
    private static function virtualTargets()
    {
        $targets = [
            'sys:search' => ['text' => '{LNG_Search}', 'url' => WEB_URL.'index.php?module=search'],
            'sys:login' => ['text' => '{LNG_Sign in}', 'url' => WEB_URL.'index.php?module=login'],
            'sys:logout' => ['text' => '{LNG_Sign out}', 'url' => WEB_URL.'index.php?action=logout'],
            'sys:register' => ['text' => '{LNG_Register}', 'url' => WEB_URL.'index.php?module=register'],
            'sys:forgot' => ['text' => '{LNG_Forgot password}', 'url' => WEB_URL.'index.php?module=forgot'],
            'sys:profile' => ['text' => '{LNG_Edit profile}', 'url' => WEB_URL.'index.php?module=profile'],
            'sys:admin' => ['text' => '{LNG_Administrator area}', 'url' => WEB_URL.'admin/']
        ];

        foreach (self::installedOwners() as $owner) {
            $class = ucfirst($owner).'\Admin\Init\Model';
            if (class_exists($class) && method_exists($class, 'initMenuTargets')) {
                foreach ((array) $class::initMenuTargets() as $key => $target) {
                    $targets[$owner.':'.$key] = $target;
                }
            }
        }

        return $targets;
    }

    /**
     * Owners (module directory names) that are actually installed
     * (have a controllers/init.php), for scanning the initMenuTargets() hook.
     *
     * @return array<string>
     */
    private static function installedOwners()
    {
        $owners = [];
        foreach ((array) glob(ROOT_PATH.'modules/*', GLOB_ONLYDIR) as $dir) {
            if (is_file($dir.'/controllers/init.php')) {
                $owners[] = basename($dir);
            }
        }

        return $owners;
    }

    /**
     * Resolve a virtual target value (from virtualTargets(), e.g. "sys:login"
     * or "poll:report") into a real URL. Returns null when $value isn't a
     * recognized virtual target — i.e. it's a plain numeric `index.id`,
     * which the caller (Adminmenu\Controller::save()) handles separately.
     *
     * @param string $value
     *
     * @return string|null
     */
    public static function resolveVirtualTarget($value)
    {
        $targets = self::virtualTargets();

        return isset($targets[$value]) ? $targets[$value]['url'] : null;
    }
}
