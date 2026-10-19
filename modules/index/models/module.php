<?php
/**
 * @filesource modules/index/models/module.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Module;

/**
 * คลาสสำหรับโหลดรายการโมดูลที่ติดตั้งแล้วทั้งหมด จากฐานข้อมูลของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * รายการโมดูล เรียงลำดับตาม owner
     *
     * @var array
     */
    private $modulesByOwner = [];

    /**
     * รายการโมดูล เรียงลำดับตาม module
     *
     * @var array
     */
    private $modulesByName = [];

    /**
     * อ่านรายชื่อโมดูลทั้งหมดที่ติดตั้งไว้
     *
     * @param string $dir ไดเรคทอรีที่ติดตั้งโมดูล
     * @param \Index\Menu\Controller $menu
     */
    public function __construct($dir, $menu)
    {
        // Initialize module containers
        $this->modulesByOwner = [];

        // Installed modules (by owner)
        $f = @opendir($dir);
        if ($f) {
            while (false !== ($owner = readdir($f))) {
                if ($owner != '.' && $owner != '..') {
                    $this->modulesByOwner[$owner] = [];
                }
            }
            closedir($f);
        }

        if (!empty(self::$cfg->modules)) {
            foreach (self::$cfg->modules as $owner) {
                $this->modulesByOwner[$owner] = [];
            }
        }

        // Load installed and active modules
        $modules = $this->getInstalledModules(array_keys($this->modulesByOwner));

        // Add module data to the menu if $menu is valid
        if ($menu instanceof \Index\Menu\Controller) {
            foreach ($menu->getAllMenus() as $item) {
                if (isset($modules[$item->index_id])) {
                    $item->module = $modules[$item->index_id];
                }
            }
        }

        // Sort module data by module name and owner
        foreach ($modules as $item) {
            $this->modulesByName[$item->module] = $item;
            $this->modulesByOwner[$item->owner][] = $item;
        }
    }

    /**
     * คืนค่ารายการโมดูลทั้งหมด เรียงลำดับตาม owner
     *
     * @return array
     */
    public function getModulesByOwner()
    {
        return $this->modulesByOwner;
    }

    /**
     * คืนค่ารายการโมดูลทั้งหมด เรียงลำดับตาม module
     *
     * @return array
     */
    public function getModulesByName()
    {
        return $this->modulesByName;
    }

    /**
     * โหลดโมดูลที่ติดตั้งแล้ว และสามารถใช้งานได้
     *
     * @param array $owners รายการโมดูลที่สามารถใช้ได้
     *
     * @return array
     */
    private function getInstalledModules($owners)
    {
        $query = \Kotchasan\Model::createQuery()
            ->select('D.id index_id', 'I.module_id', 'M.module', 'M.owner', 'M.config', 'D.topic', 'D.keywords', 'D.description', 'D.detail')
            ->from('index I')
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', ['', LANGUAGE]]])
            ->join('modules M', ['M.id', 'I.module_id'])
            ->where([
                ['I.index', 1],
                ['I.published', 1],
                ['M.owner', $owners]
            ])
            ->cacheOn();
        $result = [];

        foreach ($query->fetchAll() as $item) {
            $config = json_decode((string) $item->config);
            if (!is_object($config)) {
                $config = (object) [];
            }
            $item->config = $config;
            foreach ($config as $key => $value) {
                $item->$key = $value;
            }
            $result[$item->index_id] = $item;
        }

        return $result;
    }

    /**
     * อ่านข้อมูลโมดูลและค่ากำหนด จาก DB
     * คืนค่าข้อมูลโมดูล (Object) ไม่พบคืนค่า false
     *
     * @param string $owner
     * @param int    $module_id
     * @param string $module
     *
     * @return object|false
     */
    public static function getModuleWithConfig($owner, $module_id = 0, $module = '')
    {
        $module_id = (int) $module_id;
        if (empty($owner) && empty($module) && $module_id > 0) {
            $where = ['id', $module_id];
        } elseif (empty($owner) && empty($module_id)) {
            $where = ['module', $module];
        } elseif ($module_id > 0 && !empty($owner)) {
            $where = [['id', $module_id], ['owner', $owner]];
        } elseif (!empty($module) && !empty($owner)) {
            $where = [['module', $module], ['owner', $owner]];
        } else {
            $where = ['owner', $owner];
        }

        $search = \Kotchasan\Model::createQuery()
            ->select('id', 'module', 'owner', 'config')
            ->from('modules')
            ->where($where)
            ->first();

        if ($search) {
            $search->config = json_decode((string) $search->config);
            if (!is_object($search->config)) {
                $search->config = (object) [];
            }
            return $search;
        }
        return false;
    }

    /**
     * Save module configuration to DB
     *
     * @param int $module_id
     * @param object $config
     *
     * @return bool True if update successful, false otherwise
     */
    public static function updateConfig($module_id, $config)
    {
        $result = \Kotchasan\DB::create()->update('modules', ['id', (int) $module_id], ['config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        return $result >= 0;
    }

    /**
     * Read module details
     * topic, details, keywords, description
     * Not found, returns null.
     *
     * @param object $index
     *
     * @return object|null
     */
    public static function getModuleDetails($index)
    {
        if (!empty($index->index_id) && !empty($index->module_id)) {
            $search = \Kotchasan\Model::createQuery()
                ->select('D.topic', 'D.keywords', 'D.detail', 'D.description')
                ->from('index I')
                ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', ['', LANGUAGE]]])
                ->where([
                    ['I.id', (int) $index->index_id],
                    ['I.module_id', (int) $index->module_id]
                ])
                ->cacheOn()
                ->first();

            if ($search) {
                $index->topic = $search->topic;
                $index->detail = $search->detail;
                $index->keywords = $search->keywords;
                $index->description = $search->description;
                return $index;
            }
        }
        return null;
    }
}
