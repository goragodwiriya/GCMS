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

use Gcms\Gcms;
use Gcms\Login;

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
     * สร้างเมนูตามตำแหน่งของเมนู (parent)
     *
     * @param string $select รายการเมนูที่เลือก
     *
     * @return array รายการเมนูทั้งหมด
     */
    public static function render($menus, $select)
    {
        $obj = new static;
        $result = array();
        foreach ($menus as $parent => $items) {
            if ($parent != '') {
                $result['/{'.$parent.'}/'] = $obj->draw($items, $select);
            }
        }
        return $result;
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
        $mymenu = '';
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
        return $mymenu;
    }

    /**
     * ฟังก์ชั่นสร้างรายการเมนู
     *
     * @param array  $item   แอเรย์ข้อมูลเมนู
     * @param string $select (optional) เมนูที่ถูกเลือก
     * @param bool   $arrow  (optional) true=แสดงลูกศรสำหรับเมนูที่มีเมนูย่อย (default false)
     *
     * @return string คืนค่า HTML ของเมนู
     */
    private function createItem($item, $select = null, $arrow = false)
    {
        // module
        $module = isset($item->module) ? $item->module : null;
        $c = array();
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
        if (($module && $module->index_id > 0) || $item->menu_url != '') {
            $a = $item->menu_target == '' ? '' : ' target='.$item->menu_target;
            $a .= $item->accesskey == '' ? '' : ' accesskey='.$item->accesskey;
            if ($module && $module->index_id > 0) {
                $a .= ' href="'.Gcms::createUrl($module->module).'"';
            } else {
                $a .= ' href="'.$item->menu_url.'"';
            }
        } else {
            $a = ' tabindex=0';
        }
        $menu_text = $item->menu_text;
        $b = $item->menu_tooltip == '' ? $menu_text : $item->menu_tooltip;
        if ($b != '') {
            $a .= ' title="'.$b.'"';
        }
        if ($arrow) {
            return '<li'.$c.'><a class=menu-arrow'.$a.'><span>'.(empty($menu_text) ? '&nbsp;' : strip_tags(htmlspecialchars_decode($menu_text))).'</span></a>';
        } else {
            return '<li'.$c.'><a'.$a.'><span>'.(empty($menu_text) ? '&nbsp;' : strip_tags(htmlspecialchars_decode($menu_text))).'</span></a>';
        }
    }
}
