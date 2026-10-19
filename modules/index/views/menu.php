<?php
/**
 * @filesource modules/index/views/menu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menu;

use Web\Gcms;
use Web\Login;

/**
 * สร้างเมนูหลักของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View
{
    /**
     * ชื่อเรียกอื่นของตำแหน่งเมนู
     *
     * TOPMENU เป็นชื่อที่ใช้ในเทมเพลตรุ่นใหม่ (คู่กับ SIDEMENU/BOTTOMMENU)
     * ส่วน MAINMENU เป็นชื่อเดิมของตำแหน่งเดียวกัน ธีมเก่าจึงยังใช้ได้ต่อไป
     *
     * @var array
     */
    private static $aliases = [
        'MAINMENU' => ['TOPMENU']
    ];

    /**
     * สร้างเมนูตามตำแหน่งของเมนู (parent)
     *
     * @param array $menus
     * @param string $select รายการเมนูที่เลือก
     * @param array $result
     *
     * @return array รายการเมนูทั้งหมด
     */
    public static function render($menus, $select, &$result)
    {
        $obj = new static();
        foreach ($menus as $parent => $items) {
            if ($parent != '') {
                $html = $obj->draw($items, $select);
                $result['/{'.$parent.'}/'] = $html;
                if (isset(self::$aliases[$parent])) {
                    foreach (self::$aliases[$parent] as $alias) {
                        $result['/{'.$alias.'}/'] = $html;
                    }
                }
            }
        }
    }

    /**
     * สร้างเมนู
     *
     * @param array  $items  แอเรย์ข้อมูลเมนู
     * @param string $select (optional) เมนูที่ถูกเลือก
     *
     * @return string
     */
    private function draw($items, $select)
    {
        $mymenu = '<ul>';
        if (isset($items['toplevel'])) {
            foreach ($items['toplevel'] as $level => $name) {
                if (isset($items[$level]) && count($items[$level]) > 0) {
                    $mymenu .= $this->createItem($name, $select, true).'<ul>';
                    foreach ($items[$level] as $level2 => $item2) {
                        if ($item2->published != 0) {
                            if (isset($items[$level2]) && count($items[$level2]) > 0) {
                                $mymenu .= $this->createItem($item2, $select, true).'<ul>';
                                foreach ($items[$level2] as $item3) {
                                    $mymenu .= $this->createItem($item3).'</li>';
                                }
                                $mymenu .= '</ul></li>';
                            } else {
                                $mymenu .= $this->createItem($item2).'</li>';
                            }
                        }
                    }
                    $mymenu .= '</ul></li>';
                } elseif ($name->published != 0) {
                    $mymenu .= $this->createItem($name, $select).'</li>';
                }
            }
        }
        return $mymenu.'</ul>';
    }

    /**
     * ฟังก์ชั่นสร้างรายการเมนู
     *
     * @param object  $item   แอเรย์ข้อมูลเมนู
     * @param string $select (optional) เมนูที่ถูกเลือก
     * @param bool   $arrow  (optional) true=แสดงลูกศรสำหรับเมนูที่มีเมนูย่อย (default false)
     *
     * @return string คืนค่า HTML ของเมนู
     */
    private function createItem($item, $select = null, $arrow = false)
    {
        // module
        $module = isset($item->module) ? $item->module : null;
        $c = [];
        if ($item->alias != '') {
            $c[] = $item->alias;
            if ($select === $item->alias) {
                $c[] = 'select';
            }
        } elseif ($module && $module->module != '') {
            $c[] = $module->module;
            if ($select === $module->module) {
                $c[] = 'select';
            }
        }
        if ($item->published != '1') {
            if (Login::isMember()) {
                if ($item->published == '3') {
                    $c[] = 'hidden';
                }
            } else {
                if ($item->published == '2') {
                    $c[] = 'hidden';
                }
            }
        }
        $c = count($c) == 0 ? '' : ' class="'.implode(' ', $c).'"';
        $a = '';
        if (($module && $module->index_id > 0) || $item->menu_url != '') {
            $a = $item->menu_target == '' ? '' : ' target='.$item->menu_target;
            $a .= $item->accesskey == '' ? '' : ' accesskey='.$item->accesskey;
            if ($item->menu_target == '_blank' && $item->index_id == 0) {
                $a .= ' rel=noreferrer';
            }
            if ($module && $module->index_id > 0) {
                $a .= ' href="'.Gcms::createUrl($module->module).'"';
            } else {
                // older sites stored links as {WEB_URL}... (Index\Menu\Model::get() expands it too)
                $a .= ' href="'.str_replace('{WEB_URL}', WEB_URL, $item->menu_url).'"';
            }
        }
        // prefer menu_text but fall back to title if present
        $menu_text = isset($item->menu_text) ? $item->menu_text : (isset($item->title) ? $item->title : '');
        // Icon support for object items
        $icon = isset($item->icon) ? $item->icon : (isset($item->menu_icon) ? $item->menu_icon : '');
        $b = $item->menu_tooltip == '' ? $menu_text : $item->menu_tooltip;
        if ($b != '') {
            $a .= ' title="'.$b.'"';
        }
        $textHtml = empty($menu_text) ? '' : strip_tags(htmlspecialchars_decode($menu_text));
        $iconHtml = $icon ? '<i class="'.htmlspecialchars($icon, ENT_QUOTES, 'UTF-8').'"></i> ' : '';

        if ($arrow) {
            return '<li'.$c.'><button'.$a.'>'.$iconHtml.'<span>'.$textHtml.'</span></button>';
        }

        return '<li'.$c.'><a'.$a.'>'.$iconHtml.'<span>'.$textHtml.'</span></a>';
    }
}
