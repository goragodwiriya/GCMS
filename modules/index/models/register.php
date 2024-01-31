<?php
/**
 * @filesource modules/index/models/register.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Register;

use Gcms\Email;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 * module=register
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บันทึกข้อมูล (register.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token
        if ($request->initSession() && $request->isSafe()) {
            try {
                // รับค่าจากการ POST
                $save = array();
                foreach ($request->getParsedBody() as $key => $value) {
                    $k = str_replace('register_', '', $key);
                    switch ($k) {
                        case 'email':
                            $save['email'] = $request->post($key)->url();
                            break;
                        case 'phone1':
                        case 'idcard':
                            $save[$k] = $request->post($key)->number();
                            break;
                        case 'password':
                        case 'repassword':
                            $$k = $request->post($key)->password();
                            break;
                        case 'next':
                            $$k = $request->post($key)->url();
                            break;
                    }
                }
                if ($request->post('register_accept')->toBoolean()) {
                    // ชื่อตาราง user
                    $user_table = $this->getTableName('user');
                    // database connection
                    $db = $this->db();
                    if (in_array('email', self::$cfg->login_fields)) {
                        // อีเมล
                        if (empty($save['email'])) {
                            $ret['ret_register_email'] = 'Please fill in';
                        } elseif (!Validator::email($save['email'])) {
                            $ret['ret_register_email'] = Language::replace('Invalid :name', array(':name' => Language::get('Email')));
                        } else {
                            // ตรวจสอบอีเมลซ้ำ
                            $search = $db->first($user_table, array('email', $save['email']));
                            if ($search !== false) {
                                $ret['ret_register_email'] = Language::replace('This :name already exist', array(':name' => Language::get('Email')));
                            } elseif (preg_match('/^([A-Z]{1,1}[0-9]{0,1}\.)?([a-zA-Z0-9\._\-]+)\@.*/', $save['email'], $match)) {
                                $displayname = $match[2];
                            }
                        }
                    }
                    if (in_array('phone1', self::$cfg->login_fields)) {
                        // ตรวจสอบ phone1
                        if (!empty($save['phone1'])) {
                            // ตรวจสอบ phone1 ซ้ำ
                            $search = $this->db()->first($user_table, array('phone1', $save['phone1']));
                            if ($search) {
                                $ret['ret_register_phone1'] = Language::replace('This :name already exist', array(':name' => Language::get('Phone number')));
                            } elseif (empty($displayname)) {
                                $displayname = $save['phone1'];
                            }
                        } elseif (in_array('phone1', self::$cfg->login_fields) && self::$cfg->member_phone == 2) {
                            $ret['ret_register_phone1'] = 'Please fill in';
                        }
                    }
                    // password
                    if (mb_strlen($password) < 4) {
                        // รหัสผ่านต้องไม่น้อยกว่า 4 ตัวอักษร
                        $ret['ret_register_password'] = Language::get('Passwords must be at least four characters');
                    } elseif ($repassword != $password) {
                        // กรอกรหัสผ่านสองช่องให้ตรงกัน
                        $ret['ret_register_repassword'] = Language::get('Enter your password to match the two inputs');
                    } else {
                        $save['salt'] = uniqid();
                        $save['password'] = sha1(self::$cfg->password_key.$password.$save['salt']);
                    }
                    // phone1
                    if (!empty($save['phone1'])) {
                        if (!preg_match('/[0-9]{9,10}/', $save['phone1'])) {
                            $ret['ret_register_phone1'] = Language::replace('Invalid :name', array(':name' => Language::get('Phone number')));
                        } else {
                            // ตรวจสอบโทรศัพท์ซ้ำ
                            $search = $db->first($user_table, array('phone1', $save['phone1']));
                            if ($search !== false) {
                                $ret['ret_register_phone1'] = Language::replace('This :name already exist', array(':name' => Language::get('Phone number')));
                            }
                        }
                    } elseif (self::$cfg->member_phone == 2) {
                        $ret['ret_register_phone1'] = 'this';
                    }
                    // idcard
                    if (!empty($save['idcard'])) {
                        if (!preg_match('/[0-9]{13,13}/', $save['idcard'])) {
                            $ret['ret_register_idcard'] = Language::replace('Invalid :name', array(':name' => Language::get('Identification No.')));
                        } else {
                            // ตรวจสอบ idcard ซ้ำ
                            $search = $db->first($user_table, array('idcard', $save['idcard']));
                            if ($search !== false) {
                                $ret['ret_register_idcard'] = Language::replace('This :name already exist', array(':name' => Language::get('Identification No.')));
                            }
                        }
                    } elseif (self::$cfg->member_idcard == 2) {
                        $ret['ret_register_idcard'] = 'this';
                    }
                    if (empty($ret)) {
                        $save['create_date'] = time();
                        $save['active'] = 0;
                        $save['status'] = self::$cfg->new_register_status;
                        $save['displayname'] = $displayname;
                        $save['name'] = ucwords($displayname);
                        if (empty(self::$cfg->user_activate)) {
                            // สำหรับการเข้าระบบอัตโนมัติ
                            $save['token'] = md5(uniqid());
                        }
                        $a = 1;
                        while (true) {
                            if (false === $db->first($user_table, array('displayname', $save['displayname']))) {
                                break;
                            } else {
                                ++$a;
                                $save['displayname'] = $displayname.$a;
                            }
                        }
                        // รหัสยืนยัน
                        $save['activatecode'] = empty(self::$cfg->user_activate) ? '' : md5(uniqid());
                        // บันทึกลงฐานข้อมูล
                        $save['id'] = $db->insert($user_table, $save);
                        if (!empty($save['email'])) {
                            // ส่งอีเมล
                            $replace = array(
                                '/%EMAIL%/' => $save['email'],
                                '/%PASSWORD%/' => $password,
                                '/%ID%/' => $save['activatecode']
                            );
                            Email::send(empty(self::$cfg->user_activate) ? 2 : 1, 'member', $replace, $save['email']);
                        }
                        if (empty(self::$cfg->user_activate)) {
                            // login
                            unset($save['password']);
                            $_SESSION['login'] = $save;
                            // แสดงข้อความตอบรับการสมัครสมาชิก
                            $ret['alert'] = Language::get('Already registered and logged in');
                        } else {
                            // แสดงข้อความตอบรับการสมัครสมาชิก
                            if (empty($save['email'])) {
                                $ret['alert'] = Language::get('Already registered Please wait for review');
                            } else {
                                $ret['alert'] = Language::replace('Register successfully, We have sent complete registration information to :email', array(':email' => $save['email']));
                            }
                        }
                        // ถ้าไม่มีการกำหนดหน้าถัดไปมา กลับไปหน้าหลักเว็บไซต์
                        $ret['location'] = isset($next) ? $next : WEB_URL.'index.php';
                        // เคลียร์
                        $request->removeToken();
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
        }
        // คืนค่าเป็น JSON
        if (!empty($ret)) {
            echo json_encode($ret);
        }
    }
}
