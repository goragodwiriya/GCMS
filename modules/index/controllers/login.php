<?php
/**
 * @filesource modules/index/controllers/login.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Login;

/**
 * Controller หลัก สำหรับแสดง frontend ของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * แสดงผลกรอบ login
     *
     * @param array $login ข้อมูลการ Login
     *
     * @return string ฟอร์ม
     */
    public static function init($login)
    {
        // ฟอร์ม
        if ($login) {
            return createClass('Index\Login\View')->member($login);
        } else {
            return createClass('Index\Login\View')->login();
        }
    }
}
