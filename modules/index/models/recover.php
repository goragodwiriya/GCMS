<?php
/**
 * @filesource modules/index/models/recover.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Recover;

use Gcms\Email;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * ขอรหัสผ่านใหม่
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บันทึก
     */
    public function submit(Request $request)
    {
        $ret = array();
        // referer, session
        if ($request->initSession() && $request->isReferer()) {
            try {
                // ชื่อฟิลด์สำหรับตรวจสอบอีเมล ใช้ฟิลด์แรกจาก config
                $field = reset(self::$cfg->login_fields);
                // ตาราง user
                $table = $this->getTableName('user');
                // Database
                $db = $this->db();
                // ค่าที่ส่งมา
                $username = $request->post('forgot_email')->url();
                if ($username === '') {
                    $ret['ret_forgot_email'] = 'Please fill in';
                } else {
                    // ค้นหา username
                    $search = $db->first($table, array(array($field, $username), array('social', 0)));
                    if ($search === false) {
                        $ret['ret_forgot_email'] = Language::get('not a registered user');
                    }
                }
                if (empty($ret)) {
                    // รหัสผ่านใหม่
                    $password = substr(uniqid(), -6);
                    // ข้อมูลอีเมล
                    $replace = array(
                        '/%PASSWORD%/' => $password,
                        '/%EMAIL%/' => $search->email
                    );
                    // send mail
                    $err = Email::send(3, 'member', $replace, $search->email);
                    if ($err->error()) {
                        $ret['ret_forgot_email'] = $err->getErrorMessage();
                    } else {
                        // อัปเดตรหัสผ่านใหม่
                        $salt = uniqid();
                        $save = array(
                            'salt' => $salt,
                            'password' => sha1(self::$cfg->password_key.$password.$salt)
                        );
                        $db->update($table, (int) $search->id, $save);
                        // คืนค่า
                        $ret['alert'] = Language::get('Your message was sent successfully');
                        $location = $request->post('modal')->url();
                        $ret['location'] = $location === 'true' ? 'close' : $location;
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
                $ret['ret_forgot_email'] = Language::get('not a registered user');
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
