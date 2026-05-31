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

use Kotchasan\Language;

/**
 * Class for loading menu items
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller
{
    /**
     * Menu items are sorted by menu position.
     *
     * @var array
     */
    private $menusByPosition = [];

    /**
     * All menu items
     *
     * @var array
     */
    private $allMenus = [];

    /**
     * Create a Controller for loading menus.
     *
     * @param array $login login information
     *
     * @return static
     */
    public static function init($login)
    {
        $obj = new static();

        // Create location-based menu items from menu descriptions in Language
        $obj->initializeMenuPositions();

        // Load all menus from database
        $menus = \Index\Menu\Model::queryAllMenus();

        // Arrange menus by level and position.
        $topLevelMenus = [];
        foreach ($menus as $index => $item) {
            $obj->allMenus[] = $item;
            $obj->arrangeMenuByLevel($item, $topLevelMenus, $index);
        }

        return $obj;
    }

    /**
     * Create menu items based on location
     */
    private function initializeMenuPositions()
    {
        foreach (Language::get('MENU_PARENTS', ['MAINMENU' => 'Main Menu']) as $key => $text) {
            $this->menusByPosition[$key] = [];
        }
    }

    /**
     * Arrange menus by level
     *
     * @param object $item Menu data loaded from database.
     * @param array $topLevelMenus Top level menu order
     * @param int$index The order of the menus in the list.
     */
    private function arrangeMenuByLevel($item, &$topLevelMenus, $index)
    {
        // Normalize level to avoid undefined index notices
        $level = isset($item->level) ? (int) $item->level : 0;

        if ($level == 0) {
            // Top level menu (level 0)
            $parent = isset($item->parent) ? $item->parent : '';
            $this->menusByPosition[$parent]['toplevel'][$index] = $item;
        } elseif (isset($topLevelMenus[$level - 1])) {
            // Second level menu
            $parent = isset($item->parent) ? $item->parent : '';
            $this->menusByPosition[$parent][$topLevelMenus[$level - 1]][$index] = $item;
        }
        // Keeps the current menu level position.
        $topLevelMenus[$level] = $index;
    }

    /**
     * Restores all menu items.
     *
     * @return array All menu items
     */
    public function getAllMenus()
    {
        return $this->allMenus;
    }

    /**
     * Restores menu items sorted by position.
     *
     * @return array
     */
    public function getMenusByPosition()
    {
        return $this->menusByPosition;
    }

    /**
     * เพิ่มเมนูระดับบนสุด
     *
     * @param string      $toplvl   ชื่อเมนูระดับบนสุด
     * @param string|null $text     ข้อความแสดงบนเมนู, null หมายถึงไม่แสดง
     * @param string|null $url      URL ของเมนู, null หมายถึงไม่มีลิงก์
     * @param array|null  $submenus เมนูย่อย, null หมายถึงไม่มีเมนูย่อย
     * @param string|null $before   เพิ่มเมนูก่อนเมนูที่ระบุ, null หมายถึงเพิ่มที่ตำแหน่งสุดท้าย
     * @param string|null $target   เป้าหมายของลิงก์ (เช่น '_blank'), null หมายถึงไม่มีการระบุ
     */
    public function addTopLvlMenu($toplvl, $text, $url = null, $submenus = null, $before = null, $target = null)
    {
        if (isset($this->allMenus[$toplvl])) {
            // อัปเดตเมนูที่มีอยู่แล้ว
            $this->allMenus[$toplvl]['text'] = $text;
            if (!empty($url)) {
                $this->allMenus[$toplvl]['url'] = $url;
            }
            if (!empty($target)) {
                $this->allMenus[$toplvl]['target'] = $target;
            }
            if (!empty($submenus)) {
                foreach ($submenus as $submenu) {
                    $this->allMenus[$toplvl]['submenus'][] = $submenu;
                }
            }
        } else {
            // เพิ่มเมนูใหม่
            $menu = ['text' => $text];
            if (!empty($url)) {
                $menu['url'] = $url;
            }
            if (!empty($target)) {
                $menu['target'] = $target;
            }
            if (!empty($submenus)) {
                $menu['submenus'] = $submenus;
            }
            // จัดลำดับเมนู
            $menus = [];
            foreach ($this->allMenus as $_module => $_menus) {
                if ($_module === $before) {
                    if (isset($menus[$toplvl])) {
                        $menus[$toplvl] += $menu;
                    } else {
                        $menus[$toplvl] = $menu;
                    }
                    $menu = null;
                }
                if (isset($menus[$_module])) {
                    $menus[$_module] += $_menus;
                } else {
                    $menus[$_module] = $_menus;
                }
            }
            if (!empty($menu)) {
                $menus[$toplvl] = $menu;
            }
            $this->allMenus = $menus;
        }
    }

    /**
     * เพิ่มเมนูของโมดูลที่ติดตั้ง
     *
     * @param string      $toplvl   ชื่อเมนูระดับบนสุด
     * @param string      $text     ข้อความแสดงบนเมนู
     * @param string|null $url      URL ของเมนู, null หมายถึงไม่มีลิงก์
     * @param array|null  $submenus เมนูย่อย, null หมายถึงไม่มีเมนูย่อย
     * @param string|null $name     ชื่อของเมนูย่อย
     */
    public function add($toplvl, $text, $url = null, $submenus = null, $name = null)
    {
        if (isset($this->allMenus[$toplvl])) {
            $menu = ['text' => $text];
            if (!empty($url)) {
                $menu['url'] = $url;
            }
            if (!empty($submenus)) {
                $menu['submenus'] = $submenus;
            }
            if ($name === null) {
                $this->allMenus[$toplvl]['submenus'][] = $menu;
            } else {
                $this->allMenus[$toplvl]['submenus'][$name] = $menu;
            }
        }
    }

    /**
     * ดึงเมนูระดับบนสุดตาม index_id ที่ระบุ
     *
     * @param int $index_id ID ของเมนูที่ต้องการค้นหา
     *
     * @return object|null เมนูระดับบนสุดที่พบ, หรือ null หากไม่พบ
     */
    public function getTopLevelMenuByIndexId($index_id)
    {
        foreach ($this->allMenus as $menu) {
            if ($menu->index_id == $index_id && $menu->level == 0) {
                return $menu;
            }
        }
        return null;
    }

    /**
     * คืนค่าเมนูระดับบนสุดตามชื่อที่ระบุ
     *
     * @param string $toplvl ชื่อเมนูระดับบนสุด
     *
     * @return array|null เมนูระดับบนสุดที่พบ, หรือ null หากไม่พบ
     */
    public function getTopLvlMenu($toplvl)
    {
        return isset($this->allMenus[$toplvl]) ? $this->allMenus[$toplvl] : null;
    }

    /**
     * ลบเมนูระดับบนสุดตามชื่อที่ระบุ
     *
     * @param string $toplvl ชื่อเมนูระดับบนสุด
     */
    public function removeTopLvlMenu($toplvl)
    {
        unset($this->allMenus[$toplvl]);
    }

    /**
     * อ่านเมนูรายการแรกสุด (หน้าหลัก)
     *
     * @return object|bool เมนูรายการแรกสุดที่พบ, หรือ false หากไม่พบ
     */
    public function getHomeMenu()
    {
        $menus = reset($this->menusByPosition);
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
     * @return bool true หากเป็นหน้าแรกสุด, false หากไม่ใช่
     */
    public function isHomeMenu($index_id)
    {
        $home = $this->getHomeMenu();
        return $home && isset($home->module) && $home->module->index_id == $index_id;
    }

    /**
     * สร้างเมนูตามตำแหน่งของเมนู (parent)
     *
     * @param string $select รายการเมนูที่เลือก
     * @param array $result
     */
    public function render($select, &$result)
    {
        \Index\Menu\View::render($this->menusByPosition, $select, $result);
    }
}
