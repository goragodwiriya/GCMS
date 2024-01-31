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
class Controller extends \Gcms\Controller
{
    /**
     * ข้อมูลโมดูล
     *
     * @var \Index\Module\Model
     */
    private $module;
    /**
     * รายการส่วนเสริม
     *
     * @var array
     */
    private $widgets = array();

    /**
     * initial class
     *
     * @return static
     */
    public static function init()
    {
        // create Class
        $obj = new static;
        // ไดเร็คทอรี่ที่ติดตั้งโมดูล
        $dir = ROOT_PATH.'modules/';
        // อ่านรายชื่อโมดูลและไดเร็คทอรี่ของโมดูลทั้งหมดที่ติดตั้งไว้
        $obj->module = new \Index\Module\Model($dir);
        // ส่วนเสริมที่ติดตั้ง
        $f = @opendir(ROOT_PATH.'Widgets/');
        if ($f) {
            while (false !== ($owner = readdir($f))) {
                if ($owner != '.' && $owner != '..') {
                    $obj->widgets[] = $owner;
                }
            }
            closedir($f);
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
     * อ่านรายชื่อโมดูลที่ติดตั้งแล้วจาก owner
     *
     * @param string $owner
     *
     * @return array|null คืนค่าแอเรย์ของ object ถ้าไม่มีโมดูลติดตั้งคืนค่า null
     */
    public function findInstalledOwners($owner)
    {
        return empty($this->module->by_owner[$owner]) ? null : $this->module->by_owner[$owner];
    }

    /**
     * อ่านข้อมูลโมดูลทั้งหมด
     *
     * @return array
     */
    public function getInstalledModules()
    {
        return $this->module->by_module;
    }

    /**
     * อ่านรายการส่วนเสริมทั้งหมด
     *
     * @return array
     */
    public function getInstalledWidgets()
    {
        return $this->widgets;
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
}
