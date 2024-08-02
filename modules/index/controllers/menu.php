<?php
/**
 * @filesource modules/index/controllers/menu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menu;

/**
 * Controller สำหรับจัดการเมนู
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * ข้อมูลเมนู
     *
     * @var \Index\Menu\Model
     */
    private $menu;

    /**
     * initial class
     *
     * @return static
     */
    public static function create()
    {
        $obj = new static;
        $obj->menu = new \Index\Menu\Model();
        return $obj;
    }

    /**
     * อ่านรายการเมนูทั้งหมด
     *
     * @return array
     */
    public function getMenus()
    {
        return $this->menu->menus;
    }

    /**
     * อ่านค่าเมนูระดับบนสุดจาก index_id
     *
     * @param int $index_id
     *
     * @return object|null คืนค่าข้อมูลเมนู (Object) ไม่พบคืนค่า null
     */
    public function findTopLevelMenu($index_id)
    {
        foreach ($this->menu->menus as $menu) {
            if ($menu->index_id == $index_id && $menu->level == 0) {
                return $menu;
            }
        }
        return null;
    }

    /**
     * อ่านเมนูรายการแรกสุด (หน้าหลัก)
     *
     * @return object|bool แอเรย์ของเมนูรายการแรก ถ้าไม่พบคืนค่า false
     */
    public function homeMenu()
    {
        $menus = reset($this->menu->menus_by_pos);
        if ($menus && isset($menus['toplevel'])) {
            return reset($menus['toplevel']);
        }
        return false;
    }

    /**
     * ตรวจสอบว่าเป็นข้อมูลหน้าแรกสุดหรือไม่
     *
     * @param int $index_id ID ของตาราง Index
     *
     * @return bool
     */
    public function isHome($index_id)
    {
        $home = $this->homeMenu();
        return $home && isset($home->module) && $home->module->index_id == $index_id;
    }

    /**
     * สร้างเมนูตามตำแหน่งของเมนู (parent)
     *
     * @param string $select รายการเมนูที่เลือก
     *
     * @return array รายการเมนูทั้งหมด
     */
    public function render($select)
    {
        return \Index\Menu\View::render($this->menu->menus_by_pos, $select);
    }
}
