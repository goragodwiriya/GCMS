<?php
/**
 * @filesource modules/index/views/upgrade3.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade3;

use Kotchasan\Http\Request;
use Kotchasan\Validator;

/**
 * อัปเกรด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * อัปเกรด
     *
     * @return string
     */
    public function render(Request $request)
    {
        if (defined('INSTALL')) {
            if ($request->post('email')->exists()) {
                $_SESSION['password'] = $request->post('password')->password();
                $_SESSION['email'] = $request->post('email')->url();
            }
            if (empty($_SESSION['email']) || empty($_SESSION['password'])) {
                return createClass('Index\Upgrade2\View')->render($request, 'กรุณากรอก '.(empty($_SESSION['email']) ? 'ที่อยู่อีเมล' : 'รหัสผ่าน'));
            } elseif (!Validator::email($_SESSION['email'])) {
                return createClass('Index\Upgrade2\View')->render($request, 'ที่อยู่อีเมล ไม่ถูกต้อง');
            } else {
                // ตรวจสอบการเชื่อมต่อฐานข้อมูล
                $db = \Kotchasan\Database::create(array(
                    'username' => $_SESSION['cfg']['db_username'],
                    'password' => $_SESSION['cfg']['db_password'],
                    'dbname' => $_SESSION['cfg']['db_name'],
                    'hostname' => $_SESSION['cfg']['db_server'],
                    'prefix' => $_SESSION['prefix']
                ));
                if (!$db->connection()) {
                    return \Index\Dberror\View::create()->render($request);
                }
                $db->query('SET SQL_MODE=""');
                // ตาราง user
                $table_name = $_SESSION['prefix'].'_user';
                $result = $db->first($table_name, array(array('email', $_SESSION['email']), array('status', 1)));
                if ($result === false) {
                    return createClass('Index\Upgrade2\View')->render($request, 'ชื่อผู้ใช้ ไม่ถูกต้อง');
                } elseif ($result->password === sha1($_SESSION['password'].$result->salt)) {
                    // เวอร์ชั่นก่อน 13.4.0
                    if (empty(self::$cfg->password_key)) {
                        self::$cfg->password_key = uniqid();
                    }
                    $db->update($table_name, $result->id, array(
                        'password' => sha1(self::$cfg->password_key.$_SESSION['password'].$result->salt)
                    ));
                } elseif ($result->password === sha1(self::$cfg->password_key.$_SESSION['password'].$result->salt)) {
                    // เวอร์ชั่น 13.4.0 ขึ้นไป
                } else {
                    return createClass('Index\Upgrade2\View')->render($request, 'รหัสผ่าน ไม่ถูกต้อง');
                }
                $content = array();
                $content[] = '<h2>{TITLE}</h2>';
                $content[] = '<p>อัปเกรดเรียบร้อย ก่อนการใช้งานกรุณาตรวจสอบค่าติดตั้งต่างๆให้เรียบร้อยก่อน ทั้งการตั้งค่าเว็บไซต์ และการตั้งค่าโมดูล หากคุณต้องการความช่วยเหลือ คุณสามารถ ติดต่อสอบถามได้ที่ <a href="https://goragod.com" target="_blank">https://goragod.com</a> หรือ <a href="http://gcms.in.th" target="_blank">http://gcms.in.th</a></p>';
                $content[] = '<ul>';
                $new_version = self::$cfg->new_version;
                $current_version = self::$cfg->version;
                while ($current_version != $new_version) {
                    $ret = \Index\Upgrading\Model::upgrade($db, $current_version);
                    $content[] = $ret->content;
                    $current_version = $ret->version;
                }
                self::$cfg->version = $current_version;
                unset(self::$cfg->new_version);
                $f = \Gcms\Config::save(self::$cfg, CONFIG);
                $content[] = '<li class="'.($f ? 'correct' : 'incorrect').'">Update file <b>config.php</b> ...</li>';
                $content[] = '</ul>';
                $content[] = '<p class=warning>กรุณาลบโฟลเดอร์ <em>install/</em> ออกจาก Server ของคุณ</p>';
                $content[] = '<p>เมื่อเรียบร้อยแล้ว กรุณา<b>เข้าระบบผู้ดูแล</b>เพื่อตั้งค่าที่จำเป็นอื่นๆโดยใช้ขื่ออีเมลและรหัสผ่านเก่าของคุณ</p>';
                $content[] = '<p class=error><b>คำเตือน</b> ตัวอัปเกรด ไม่สามารถนำเข้าข้อมูลได้ทุกอย่าง หลังการอัปเกรด จะต้องตรวจสอบค่ากำหนดต่างๆ ให้ถูกต้องด้วยตัวเองอีกครั้ง เช่น การตั้งค่าเว็บไซต์ การตั้งค่าโมดูล และ การตั้งค่าหมวดของหมวดหมู่ต่างๆ</p>';
                $content[] = '<p><a href="'.WEB_URL.'admin/index.php?module=system" class="button large admin">เข้าระบบผู้ดูแล</a></p>';
                return (object) array(
                    'title' => 'อัปเกรด GCMS เป็นเวอร์ชั่น '.self::$cfg->version.' เรียบร้อย',
                    'content' => implode('', $content)
                );
            }
        }
    }
}
