<?php
/**
 * @filesource module.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Module;

/**
 * Description
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * ข้อมูลโมดูล
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
    public static function init($menu, $new_day)
    {
        // create Class
        $obj = new static;
        // ไดเร็คทอรี่ที่ติดตั้งโมดูล
        $dir = ROOT_PATH.'modules/';
        // อ่านรายชื่อโมดูลและไดเร็คทอรี่ของโมดูลทั้งหมดที่ติดตั้งไว้
        $obj->module = new \Index\Module\Model($dir, $menu);
        if (MAIN_INIT == 'indexhtml') {
            // โหลดโมดูลที่ติดตั้งแล้ว และสามารถใช้งานได้
            foreach ($obj->module->by_owner as $owner => $modules) {
                if (is_file($dir.$owner.'/controllers/init.php')) {
                    include $dir.$owner.'/controllers/init.php';
                    $class = ucfirst($owner).'\Init\Controller';
                    if (method_exists($class, 'init')) {
                        createClass($class)->init($modules);
                    }
                }
            }
            if ($new_day) {
                // cron
                createClass('Index\Cron\Controller')->index(self::$request);
            }
            // โหลด init ของส่วนเสริม
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
        // คืนค่า Class

        return $obj;
    }

    /**
     * อ่านข้อมูลโมดูลทั้งหมด จากชื่อไดเร็คทอรี่
     *
     * @return array
     */
    public function getInstalledOwners()
    {
        return $this->module->by_owner;
    }

    /**
     * ตรวจสอบโมดูลที่เรียก
     *
     * @param array $modules ข้อมูลจาก $_GET หรือ $_POST
     *
     * @return object||null คืนค่าโมดูลที่ใช้งานได้ ไม่พบคืนค่า null
     */
    public function checkModuleCalled($modules)
    {
        // รายชื่อโมดูลทั้งหมด
        $module_list = array_keys($this->module->by_module);
        // ตรวจสอบโมดูลที่เรียก
        if (isset($modules['module']) && preg_match('/^(tag|calendar)([\/\-](.*)|)$/', $modules['module'], $match)) {
            // โมดูล document (tag, calendar)
            $modules['module'] = 'document';
            $modules['page'] = ucfirst($match[1]);
            if (isset($match[3])) {
                $modules['alias'] = $match[3];
            }
        } elseif (isset($modules['module']) && preg_match('/^([a-z0-9]+)[\/\-]([a-z]+)$/', $modules['module'], $match)) {
            // โมดูลที่ติดตั้ง
            $modules['module'] = $match[1];
            $modules['page'] = ucfirst($match[2]);
        } else {
            // โมดูล index
            $modules['page'] = 'Index';
        }
        // ตรวจสอบโมดูลที่เลือกกับโมดูลที่ติดตั้งแล้ว
        $module = null;
        if (!empty($module_list)) {
            if (empty($modules['module'])) {
                // ไม่ได้กำหนดโมดูลมา ใช้โมดูลแรกสุด
                $module = $this->module->by_module[reset($module_list)];
            } elseif ($modules['module'] == 'search') {
                // เรียกหน้าค้นหา (โมดูล index)
                $module = (object) array(
                    'owner' => 'search'
                );
            } elseif ($modules['module'] == 'index' && isset($modules['id'])) {
                // เรียกโมดูล index จาก id
                $module = self::findByIndexId($modules['id']);
            } elseif (in_array($modules['module'], $module_list)) {
                // โมดูลที่เลือก
                $module = $this->module->by_module[$modules['module']];
            } elseif (in_array($modules['module'], array_keys($this->module->by_owner))) {
                // เรียกโมดูลที่ติดตั้ง (ไดเร็คทอรี่)
                $modules['owner'] = $modules['module'];
                $module = (object) $modules;
            }
        }
        if ($module) {
            if ($module->owner == 'index') {
                // เรียกจากโมดูล index
                $className = 'Index\Main\Controller';
            } elseif ($module->owner == 'search') {
                // ค้นหา
                $className = 'Index\Search\Controller';
                $module->owner = 'index';
                $module->module = 'search';
                $module->page = 'init';
            } else {
                // เรียกจากโมดูลที่ติดตั้ง
                $className = ucfirst($module->owner).'\\'.$modules['page'].'\Controller';
                if (!class_exists($className)) {
                    $className = null;
                }
            }
            // เรียก method init
            $method = 'init';
        } elseif (!empty($modules['module']) &&
            class_exists('Index\Member\Controller') &&
            method_exists('Index\Member\Controller', $modules['module'])) {
            // หน้าสมาชิก
            $className = 'Index\Member\Controller';
            // method ที่เลือก
            $method = $modules['module'];
        }
        if (empty($className)) {
            return null;
        }
        return (object) array(
            'className' => $className,
            'method' => $method,
            'module' => $module
        );
    }

    /**
     * อ่านชื่อโมดูลแรกสุด
     *
     * @return string ไม่พบคืนค่าข้อความว่าง
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
     * อ่านข้อมูลโมดูลทั้งหมด จากชื่อไดเร็คทอรี่
     *
     * @param string $owner
     *
     * @return array
     */
    public function findByOwner($owner)
    {
        return isset($this->module->by_owner[$owner]) ? $this->module->by_owner[$owner] : array();
    }

    /**
     * อ่านข้อมูลโมดูลจากชื่อโมดูล
     *
     * @param string $module ชื่อโมดูล
     *
     * @return object|null ข้อมูลโมดูล (Object) ไม่พบคืนค่า null
     */
    public function findByModule($module)
    {
        return isset($this->module->by_module[$module]) ? $this->module->by_module[$module] : null;
    }

    /**
     * อ่านข้อมูลโมดูลจาก ID ของโมดูล
     *
     * @param int $id ID ของโมดูล
     *
     * @return object|null ข้อมูลโมดูล (Object) ไม่พบคืนค่า null
     */
    public function findByID($id)
    {
        if (!empty($this->module->by_module)) {
            foreach ($this->module->by_module as $item) {
                if ($item->module_id == $id) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     * อ่านข้อมูลโมดูลจาก index_id ของโมดูล
     *
     * @param int $id ID ของโมดูล
     *
     * @return object|null ข้อมูลโมดูล (Object) ไม่พบคืนค่า null
     */
    public function findByIndexId($id)
    {
        if (!empty($this->module->by_module)) {
            foreach ($this->module->by_module as $item) {
                if ($item->index_id == $id) {
                    return $item;
                }
            }
        }
        return null;
    }
}
