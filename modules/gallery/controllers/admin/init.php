<?php
/**
 * @filesource modules/gallery/controllers/admin/init.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Init;

use Gcms\Gcms;
use Gcms\Login;

/**
 * จัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * ฟังก์ชั่นเรียกโดย admin สำหรับการสร้างเมนู
     *
     * @param array $modules
     * @param array $login
     */
    public static function init($modules, $login)
    {
        if (!empty($modules) && $login && isset(Gcms::$menu)) {
            // เมนู
            foreach ($modules as $item) {
                if (Gcms::canConfig($login, $item, 'can_config') || !Login::notDemoMode($login)) {
                    Gcms::$menu->menus['modules'][$item->module]['config'] = '<a href="index.php?module=gallery-settings&amp;mid='.$item->id.'"><span>{LNG_Config}</span></a>';
                }
                if (Gcms::canConfig($login, $item, 'can_write') || !Login::notDemoMode($login)) {
                    Gcms::$menu->menus['modules'][$item->module]['setup'] = '<a href="index.php?module=gallery-setup&amp;mid='.$item->id.'"><span>{LNG_List of} {LNG_Album}</span></a>';
                    Gcms::$menu->menus['modules'][$item->module]['write'] = '<a href="index.php?module=gallery-write&amp;mid='.$item->id.'"><span>{LNG_Add New} {LNG_Album}</span></a>';
                }
            }
        }
    }

    /**
     * คำอธิบายเกี่ยวกับโมดูล ถ้าไม่มีฟังก์ชั่นนี้ โมดูลนี้จะไม่สามารถใช้ซ้ำได้
     */
    public static function description()
    {
        return '{LNG_Module} {LNG_Gallery}';
    }
}
