<?php
/**
 * @filesource modules/index/models/menu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Module;

/**
 * Class for loading all module and widget data.
 * and run Cron if it's the first time of the day
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Module data
     *
     * @var \Index\Module\Model
     */
    private $module;

    /**
     * Menu data (used to find the home page)
     *
     * @var \Index\Menu\Controller|null
     */
    private $menu;

    /**
     * initial class
     *
     * @param \Index\Menu\Controller $menu
     * @param bool                   $new_day true เรียกครั้งแรกของวัน
     *
     * @return static
     */
    public static function init($menu, $new_day = false)
    {
        // create Class
        $obj = new static();
        // Directory where modules are installed
        $dir = ROOT_PATH.'modules/';
        // Read the names of modules and directories of all installed modules
        $obj->module = new \Index\Module\Model($dir, $menu);
        $obj->menu = $menu instanceof \Index\Menu\Controller ? $menu : null;
        if (MAIN_INIT == 'indexhtml') {
            // Load installed modules and can be used
            foreach ($obj->module->getModulesByOwner() as $owner => $modules) {
                if (is_file($dir.$owner.'/controllers/init.php')) {
                    include $dir.$owner.'/controllers/init.php';
                    $class = ucfirst($owner).'\Init\Controller';
                    if (method_exists($class, 'init')) {
                        createClass($class)->init($modules);
                    }
                }
                if ($new_day && is_file($dir.$owner.'/controllers/cron.php')) {
                    include $dir.$owner.'/controllers/cron.php';
                    $class = ucfirst($owner).'\Cron\Controller';
                    if (method_exists($class, 'init')) {
                        createClass($class)->init($modules);
                    }
                }
            }
            // Load init of widgets
            $dir = ROOT_PATH.'Widgets/';
            $f = @opendir($dir);
            if ($f) {
                while (false !== ($text = readdir($f))) {
                    if ($text != '.' && $text != '..') {
                        if (is_dir($dir.$text)) {
                            if (is_file($dir.$text.'/Controllers/Init.php')) {
                                include $dir.$text.'/Controllers/Init.php';
                                $class = 'Widgets\\'.ucfirst($text).'\Controllers\Init';
                                if (method_exists($class, 'init')) {
                                    createClass($class)->init();
                                }
                            }
                        }
                    }
                }
                closedir($f);
            }
        }
        if ($new_day) {
            // บันทึกเวลาที่ cron ทำงาน
            $f = @fopen(ROOT_PATH.DATA_FOLDER.'index.php', 'wb');
            if ($f) {
                fwrite($f, date('d-m-Y H:i:s'));
                fclose($f);
            }
        }
        // Return Class
        return $obj;
    }

    /**
     * Get all module data from directory names
     *
     * @return array
     */
    public function getInstalledOwners()
    {
        return $this->module->getModulesByOwner();
    }

    /**
     * Check the called module
     *
     * @param array $modules Data from $_GET or $_POST
     *
     * @return object||null Returns the usable module, or null if not found
     */
    public function checkModuleCalled($modules)
    {
        $modulesByName = $this->module->getModulesByName();
        // List of all modules
        $module_list = array_keys($modulesByName);
        // หน้าสมาชิกที่ชื่อมี - เช่น reset-password (ลิงก์ในอีเมลขอรหัสผ่านใหม่)
        // Router ของ Kotchasan รับชื่อโมดูลเฉพาะ a-z0-9_ ชื่อแบบนี้จึงมาเป็น alias
        // (หรือถูกแยกเป็น module-page ด้านล่าง) ถ้าไม่ดักไว้ก่อนจะได้หน้าแรกแทน
        // ชื่อเมธอดของ PHP มี - ไม่ได้ จึงตัด - ออก (reset-password → resetpassword)
        $memberPage = !empty($modules['module']) ? $modules['module'] : (isset($modules['alias']) ? $modules['alias'] : '');
        if (is_string($memberPage) && preg_match('/^[a-z]+(\-[a-z]+)+$/', $memberPage)) {
            $method = str_replace('-', '', $memberPage);
            if (!in_array($method, $module_list) && method_exists('Index\Member\Controller', $method)) {
                return (object) [
                    'className' => 'Index\Member\Controller',
                    'method' => $method,
                    'module' => null
                ];
            }
        }
        // true = หน้าระดับโฟลเดอร์ของโมดูล (tag, calendar) ไม่ผูกกับโมดูลที่ติดตั้งตัวใดตัวหนึ่ง
        $ownerPage = false;
        // Check the called module
        if (isset($modules['module']) && preg_match('/^(tag|calendar)([\/\-](.*)|)$/', $modules['module'], $match)) {
            // Document module (tag, calendar)
            $ownerPage = true;
            $modules['module'] = 'document';
            $modules['page'] = ucfirst($match[1]);
            if (isset($match[3])) {
                $modules['alias'] = $match[3];
            }
        } elseif (isset($modules['module']) && preg_match('/^([a-z0-9]+)[\/\-]([a-z]+)$/', $modules['module'], $match)) {
            // Installed module
            $modules['module'] = $match[1];
            $modules['page'] = ucfirst($match[2]);
        } else {
            // Index module
            $modules['page'] = 'Index';
        }

        // Check the selected module against the installed modules
        $module = null;
        if (!empty($module_list)) {
            if (empty($modules['module'])) {
                // No module specified, use the home page (first menu item).
                // Falling back to the first installed module is order-dependent:
                // the query has no ORDER BY, so a home page whose row id is
                // higher than another page's (e.g. the Thai copy of home added
                // after "contactus") would lose to that page.
                $home = $this->menu ? $this->menu->getHomeMenu() : false;
                $module = !empty($home->module) ? $home->module : $modulesByName[reset($module_list)];
            } elseif ($modules['module'] === 'search') {
                // Call search page (index module)
                $module = (object) [
                    'owner' => 'search'
                ];
            } elseif ($modules['module'] === 'index' && !empty($modules['id'])) {
                // Call index module by id
                $module = self::findByIndexId($modules['id']);
            } elseif (in_array($modules['module'], $module_list)) {
                // Selected module
                $module = $modulesByName[$modules['module']];
            } elseif ($ownerPage && in_array($modules['module'], array_keys($this->module->getModulesByOwner()))) {
                // Call installed module (directory) — เฉพาะหน้าระดับโฟลเดอร์ (tag, calendar)
                // ที่รับค่าจาก URL แทนข้อมูลโมดูลได้ หน้าอื่น (Index, write ฯลฯ) ต้องใช้ข้อมูล
                // ของโมดูลที่ติดตั้งจริง (index_id, module_id, topic, config) — เดิม /event
                // บนเว็บที่ไม่ได้ติดตั้งปฏิทิน หรือ /board /document /personnel ได้หน้าที่มีแต่
                // Warning (Undefined property $index_id ...) ตอนนี้เป็น 404
                $modules['owner'] = $modules['module'];
                $module = (object) $modules;
            }
        }
        if ($module) {
            if ($module->owner === 'index') {
                if ($module->module === 'home') {
                    // หน้า Home
                    $className = 'Home\Index\Controller';
                } else {
                    // เรียกจากโมดูล index
                    $className = 'Index\Main\Controller';
                }
            } elseif ($module->owner === 'search') {
                // Search
                $className = 'Index\Search\Controller';
                $module->owner = 'index';
                $module->module = 'search';
                $module->page = 'init';
            } else {
                // Called from installed module
                $className = ucfirst($module->owner).'\\'.$modules['page'].'\Controller';
                if (!self::isPageController($className)) {
                    $className = null;
                }
            }
            // Call method init
            $method = 'init';
        } elseif (!empty($modules['module']) &&
            class_exists('Index\Member\Controller') &&
            method_exists('Index\Member\Controller', $modules['module'])) {
            // Member page
            $className = 'Index\Member\Controller';
            // selected method
            $method = $modules['module'];
        }
        if (empty($className)) {
            return null;
        }
        return (object) [
            'className' => $className,
            'method' => $method,
            'module' => $module
        ];
    }

    /**
     * คลาสนี้เป็นหน้าเว็บของโมดูลหรือไม่ — init(Request $request, $index)
     * คอนโทรลเลอร์อื่นในโฟลเดอร์เดียวกันเรียกผ่าน index.php?module=owner-page ไม่ได้
     * เช่น API (Event\Calendar ไม่มี init) หรือ sitemap (init($ids, $modules, $date))
     * เดิมถูกเรียกเป็นหน้าเว็บแล้ว Fatal error
     *
     * @param string $className
     *
     * @return bool
     */
    private static function isPageController($className)
    {
        if (!class_exists($className) || !method_exists($className, 'init')) {
            return false;
        }
        $params = (new \ReflectionMethod($className, 'init'))->getParameters();
        $type = isset($params[0]) ? $params[0]->getType() : null;

        return $type instanceof \ReflectionNamedType && is_a($type->getName(), 'Kotchasan\Http\Request', true);
    }

    /**
     * Get the first module name
     *
     * @return string Returns an empty string if not found
     */
    public function getFirst()
    {
        if (empty($this->module->by_module)) {
            return '';
        } else {
            reset($this->module->by_module);
            return key($this->module->by_module);
        }
    }

    /**
     * Get all module data by directory name
     *
     * @param string $owner
     *
     * @return array
     */
    public function findByOwner($owner)
    {
        $modules = $this->module->getModulesByOwner();
        return isset($modules[$owner]) ? $modules[$owner] : [];
    }

    /**
     * Get module data by module name
     *
     * @param string $module Module name
     *
     * @return object|null Module data (Object) or null if not found
     */
    public function findByModule($module)
    {
        $modules = $this->module->getModulesByName();
        return isset($modules[$module]) ? $modules[$module] : null;
    }

    /**
     * Get module data by module ID
     *
     * @param int $id Module ID
     *
     * @return object|null Module data (Object) or null if not found
     */
    public function findByID($id)
    {
        $modules = $this->module->getModulesByName();
        if (!empty($modules)) {
            foreach ($modules as $item) {
                if ($item->module_id == $id) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     * Get module data by index_id
     *
     * @param int $id Module index_id
     *
     * @return object|null Module data (Object) or null if not found
     */
    public function findByIndexId($id)
    {
        $modules = $this->module->getModulesByName();
        if (!empty($modules)) {
            foreach ($modules as $item) {
                if ($item->index_id == $id) {
                    return $item;
                }
            }
        }
        return null;
    }
}
