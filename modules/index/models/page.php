<?php
/**
 * @filesource modules/index/models/page.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Page;

use Kotchasan\Language;

/**
 * Class for loading page items from the GCMS database
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get page by ID
     * id = 0 return new page with default values
     *
     * @param int    $id
     * @param string $owner
     *
     * @return object|null Return page object, null if not found
     */
    public static function get($id, $owner)
    {
        if (empty($id)) {
            // new page, return default values
            return (object) [
                'owner' => $owner,
                'id' => 0,
                'published' => 1,
                'module' => '',
                'topic' => '',
                'keywords' => [],
                'description' => '',
                'detail' => '',
                'updated_at' => 0,
                'published_date' => date('Y-m-d'),
                'language' => '',
                'module_id' => 0
            ];
        }
        // find existing page from database
        $index = static::createQuery()
            ->select(
                'D.id',
                'D.language',
                'D.topic',
                'D.keywords',
                'D.description',
                'D.detail',
                'I.updated_at',
                'I.published',
                'I.published_date',
                'M.module',
                'M.owner',
                'D.module_id'
            )
            ->from('index I')
            ->join('modules M', ['M.id', 'I.module_id'])
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
            ->where([
                ['I.id', $id],
                ['I.index', 1]
            ])
            ->first();
        if ($index) {
            $index->keywords = empty($index->keywords) ? [] : explode(',', $index->keywords);
            $index->detail = str_replace(
                ['&#x007B;', '&#x007D;', '&#92;', '{WEBURL}'],
                ['{', '}', '\\', WEB_URL],
                $index->detail
            );
        }
        return $index;
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
            ->select(['I.id', 'M.owner', 'M.module', 'D.topic', 'I.language'])
            ->from('index I')
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', 'I.language']])
            ->join('modules M', ['M.id', 'I.module_id'])
            ->where(['I.index', 1])
            ->orderBy('M.owner')
            ->orderBy('M.module')
            ->orderBy('I.module_id')
            ->orderBy('I.language');
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

    /**
     * Owner types that can be picked when creating a new module instance —
     * any modules/{owner}/controllers/index.php folder (a real installable
     * page type, as opposed to a utility-only folder like payment/sysadmin
     * which has no index.php), minus the framework-reserved `index`/`home`
     * owners. Owners flagged $singleton on their Init\Controller are
     * dropped once one instance already exists (see isSingletonInstalled()).
     *
     * @return array [{value, text}, ...]
     */
    public static function getAvailableOwners()
    {
        $reserved = ['index', 'home'];
        $dir = ROOT_PATH.'modules/';
        $result = [];

        $f = @opendir($dir);
        if (!$f) {
            return $result;
        }
        while (false !== ($owner = readdir($f))) {
            if ($owner === '.' || $owner === '..' || in_array($owner, $reserved, true)) {
                continue;
            }
            if (!is_file($dir.$owner.'/controllers/index.php')) {
                continue;
            }
            if (self::isSingletonInstalled($owner)) {
                continue;
            }
            $result[] = ['value' => $owner, 'text' => ucfirst($owner)];
        }
        closedir($f);

        usort($result, fn($a, $b) => strcmp($a['value'], $b['value']));

        return $result;
    }

    /**
     * Whether $owner is both flagged $singleton (on its Init\Controller)
     * and already has an installed instance — the single source of truth
     * for both hiding it from getAvailableOwners() and rejecting a second
     * install in Index\Adminpage\Controller::validateFields().
     *
     * @param string $owner
     *
     * @return bool
     */
    public static function isSingletonInstalled($owner)
    {
        if (empty($owner) || !self::ownerIsSingleton($owner)) {
            return false;
        }

        $existing = static::createQuery()
            ->from('modules')
            ->where(['owner', $owner])
            ->first();

        return !empty($existing);
    }

    /**
     * The class that declares $owner's defaultSettings()/install() hooks.
     * Both layouts in use here are supported: {Owner}\Settings\Controller
     * (modules/{owner}/controllers/settings.php) and {Owner}\Settings\Model
     * (modules/{owner}/models/settings.php).
     *
     * @param string $owner
     *
     * @return string|null class name, null if the owner declares no settings class
     */
    public static function settingsClass($owner)
    {
        if (empty($owner)) {
            return null;
        }
        foreach ([ucfirst($owner).'\Settings\Controller', ucfirst($owner).'\Settings\Model'] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }
        return null;
    }

    /**
     * Initial `modules`.`config` for a new instance of $owner: the owner's
     * defaultSettings() as JSON (the format Index\Module\Model reads back),
     * or '{}' when the owner declares none. The column is NOT NULL with no
     * default, so this always returns a storable string.
     *
     * @param string $owner
     *
     * @return string JSON
     */
    public static function defaultConfig($owner)
    {
        $class = self::settingsClass($owner);
        if ($class && method_exists($class, 'defaultSettings')) {
            $config = $class::defaultSettings();
            if (is_array($config) || is_object($config)) {
                return json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
        return '{}';
    }

    /**
     * @param string $owner
     *
     * @return bool
     */
    private static function ownerIsSingleton($owner)
    {
        $initFile = ROOT_PATH.'modules/'.$owner.'/controllers/init.php';
        if (!is_file($initFile)) {
            return false;
        }

        include_once $initFile;
        $class = ucfirst($owner).'\Init\Controller';

        return class_exists($class) && property_exists($class, 'singleton') && !empty($class::$singleton);
    }
}
