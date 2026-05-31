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
        // Check the called module
        if (isset($modules['module']) && preg_match('/^(tag|calendar)([\/\-](.*)|)$/', $modules['module'], $match)) {
            // Document module (tag, calendar)
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
                // No module specified, use the first module
                $module = $modulesByName[reset($module_list)];
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
            } elseif (in_array($modules['module'], array_keys($this->module->getModulesByOwner()))) {
                // Call installed module (directory)
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
                if (!class_exists($className)) {
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
