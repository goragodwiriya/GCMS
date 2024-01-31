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

/**
 * แสดงผลเมนูหลัก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View
{
    /**
     * สร้างเมนู
     *
     * @param array  $menus
     * @param string $select
     *
     * @return string
     */
    public static function render($menus, $select)
    {
        // แสดงผลเมนู
        $mymenu = '';
        foreach ($menus['sections'] as $section => $name) {
            if (preg_match('/<a.*>.*<\/a>/', $name[1])) {
                // top level menu
                $link = $name[1];
            } elseif (empty($menus[$section])) {
                // ไม่มีเมนูย่อย ไปแสดงรายการถัดไป
                continue;
            } else {
                // submenu
                $link = '<a accesskey='.$name[0].' class=menu-arrow><span>'.$name[1].'</span></a>';
            }
            $mymenu .= '<li class="'.$section.($section == $select ? ' select' : '').'">'.$link;
            if (!empty($menus[$section])) {
                $mymenu .= '<ul>';
                foreach ($menus[$section] as $key => $value) {
                    if (is_array($value)) {
                        $mymenu .= '<li class="'.$key.'"><a class=menu-arrow tabindex=0><span>{LNG_'.ucfirst($key).'}</span></a><ul>';
                        foreach ($value as $key2 => $value2) {
                            $mymenu .= '<li class="'.$key2.'">'.$value2.'</li>';
                        }
                        $mymenu .= '</ul></li>';
                    } else {
                        $mymenu .= '<li class="'.$key.'">'.$value.'</li>';
                    }
                }
                $mymenu .= '</ul>';
            }
            $mymenu .= '</li>';
        }
        return $mymenu;
    }
}
