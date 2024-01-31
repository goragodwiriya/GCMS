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

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

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
        // session, token, admin, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isAdmin()) {
            if (Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = array(
                        'status' => $request->post('register_status')->toInt()
                    );
                    if (in_array('email', self::$cfg->login_fields)) {
                        $save['email'] = $request->post('register_email')->url();
                    }
                    if (in_array('phone1', self::$cfg->login_fields)) {
                        $save['phone1'] = $request->post('register_phone1')->number();
                    }
                    $permission = $request->post('register_permission', array())->topic();
                    // Database
                    $db = $this->db();
                    // ตาราง user
                    $user_table = $this->getTableName('user');
                    // ตรวจสอบ email
                    if (!empty($save['email'])) {
                        // ตรวจสอบ email ซ้ำ
                        $search = $db->first($user_table, array('email', $save['email']));
                        if ($search) {
                            $ret['ret_register_email'] = Language::replace('This :name already exist', array(':name' => Language::get('Email')));
                        } elseif (preg_match('/^([A-Z]{1,1}[0-9]{0,1}\.)?([a-zA-Z0-9\._\-]+)\@.*/', $save['email'], $match)) {
                            $displayname = $match[2];
                        }
                    } elseif (in_array('email', self::$cfg->login_fields)) {
                        $ret['ret_register_email'] = 'Please fill in';
                    }
                    // ตรวจสอบ phone1
                    if (!empty($save['phone1'])) {
                        // ตรวจสอบ phone1 ซ้ำ
                        $search = $db->first($user_table, array('phone1', $save['phone1']));
                        if ($search) {
                            $ret['ret_register_phone1'] = Language::replace('This :name already exist', array(':name' => Language::get('Phone number')));
                        }
                    } elseif (in_array('phone1', self::$cfg->login_fields) && self::$cfg->member_phone == 2) {
                        $ret['ret_register_phone1'] = 'Please fill in';
                    }
                    // password
                    $password = $request->post('register_password')->password();
                    $repassword = $request->post('register_repassword')->password();
                    if (mb_strlen($password) < 4) {
                        // รหัสผ่านต้องไม่น้อยกว่า 4 ตัวอักษร
                        $ret['ret_register_password'] = 'this';
                    } elseif ($repassword != $password) {
                        // กรอกรหัสผ่านสองช่องให้ตรงกัน
                        $ret['ret_register_repassword'] = 'this';
                    } else {
                        $save['password'] = $password;
                    }
                    if (empty($ret)) {
                        $save['name'] = ucwords($displayname);
                        $save['displayname'] = $displayname;
                        $a = 1;
                        while (true) {
                            if (false === $db->first($user_table, array('displayname', $save['displayname']))) {
                                break;
                            } else {
                                ++$a;
                                $save['displayname'] = $displayname.$a;
                            }
                        }
                        // ลงทะเบียนสมาชิกใหม่
                        self::execute($this, $save, $permission);
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'index.php?module=member';
                        // เคลียร์
                        $request->removeToken();
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * ลงทะเบียนสมาชิกใหม่
     * คืนค่าแอเรย์ของข้อมูลสมาชิกใหม่
     *
     * @param Model $model
     * @param array $save       ข้อมูลสมาชิก
     * @param array $permission
     *
     * @return array
     */
    public static function execute($model, $save, $permission = null)
    {
        // permission ถ้าเป็น null สามารถทำได้ทั้งหมด
        if ($permission === null) {
            $permission = array_keys(\Gcms\Controller::getPermissions());
        }
        if (!isset($save['email'])) {
            $save['email'] = '';
        }
        if (!isset($save['password'])) {
            $save['password'] = '';
        } else {
            $save['salt'] = uniqid();
            $save['password'] = sha1(self::$cfg->password_key.$save['password'].$save['salt']);
        }
        if (!isset($save['country'])) {
            $save['country'] = 'TH';
        }
        $save['permission'] = empty($permission) ? '' : ','.implode(',', $permission).',';
        $save['active'] = 0;
        $save['ban'] = 0;
        $save['create_date'] = time();
        // บันทึกลงฐานข้อมูล
        $save['id'] = $model->db()->insert($model->getTableName('user'), $save);
        // คืนค่าแอเรย์ของข้อมูลสมาชิกใหม่
        $save['permission'] = array();
        foreach ($permission as $key => $value) {
            $save['permission'][] = $value;
        }
        return $save;
    }
}
