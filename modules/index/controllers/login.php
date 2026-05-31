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
 * สำหรับแสดงกรอบ Login
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
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
            return \Index\Login\View::create()->member($login);
        } else {
            return \Index\Login\View::create()->login();
        }
    }
}
