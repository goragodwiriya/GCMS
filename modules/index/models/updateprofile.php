<?php
/**
 * @filesource modules/index/models/updateprofile.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Updateprofile;

use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=editprofile
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * แก้ไขข้อมูลสมาชิก (editprofile.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, สมาชิก และไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = array();
                    foreach ($request->getParsedBody() as $key => $value) {
                        $k = str_replace('register_', '', $key);
                        switch ($k) {
                            case 'phone1':
                            case 'phone2':
                            case 'provinceID':
                            case 'zipcode':
                            case 'idcard':
                                $save[$k] = $request->post($key)->number();
                                break;
                            case 'sex':
                            case 'displayname':
                            case 'name':
                            case 'company':
                            case 'address1':
                            case 'address2':
                            case 'province':
                            case 'country':
                                $save[$k] = $request->post($key)->topic();
                                break;
                            case 'email':
                            case 'website':
                                $save[$k] = $request->post($key)->url();
                                break;
                            case 'birthday':
                                $save[$k] = $request->post($key)->date();
                                break;
                            case 'password':
                            case 'repassword':
                                $$k = $request->post($key)->password();
                                break;
                        }
                    }
                    if (isset($save['country']) && $save['country'] == 'TH') {
                        // จังหวัดจาก provinceID ถ้าเลือกประเทศไทย
                        $save['province'] = \Kotchasan\Province::get($save['provinceID']);
                    }
                    // ชื่อตาราง user
                    $user_table = $this->getTableName('user');
                    // database connection
                    $db = $this->db();
                    // ตรวจสอบค่าที่ส่งมา
                    $user = $db->first($user_table, $request->post('register_id')->toInt());
                    if ($user && $user->id === $login['id']) {
                        // อีเมล
                        if (in_array('email', self::$cfg->login_fields) || $user->social > 0) {
                            unset($save['email']);
                        } elseif (!empty($save['email'])) {
                            // ตรวจสอบอีเมลซ้ำ
                            $search = $db->first($user_table, array('email', $save['email']));
                            if ($search !== false && $user->id != $search->id) {
                                $ret['ret_register_email'] = Language::replace('This :name already exist', array(':name' => Language::get('Email')));
                            }
                        }
                        // โทรศัพท์
                        if (in_array('phone1', self::$cfg->login_fields)) {
                            unset($save['phone1']);
                        } elseif (!empty($save['phone1'])) {
                            if (!preg_match('/[0-9]{9,10}/', $save['phone1'])) {
                                $ret['ret_register_phone1'] = Language::replace('Invalid :name', array(':name' => Language::get('Phone number')));
                            } else {
                                // ตรวจสอบโทรศัพท์ซ้ำ
                                $search = $db->first($user_table, array('phone1', $save['phone1']));
                                if ($search !== false && $user->id != $search->id) {
                                    $ret['ret_register_phone1'] = Language::replace('This :name already exist', array(':name' => Language::get('Phone number')));
                                }
                            }
                        }
                        // ชื่อเล่น
                        if (isset($save['displayname'])) {
                            if (mb_strlen($save['displayname']) < 2) {
                                $ret['ret_register_displayname'] = Language::get('Name for the show on the site at least 2 characters');
                            } elseif (in_array($save['displayname'], self::$cfg->member_reserv)) {
                                $ret['ret_register_displayname'] = Language::get('Invalid name');
                            } else {
                                // ตรวจสอบ displayname ซ้ำ
                                $search = $db->first($user_table, array('displayname', $save['displayname']));
                                if ($search !== false && $user->id != $search->id) {
                                    $ret['ret_register_displayname'] = Language::replace('This :name already exist', array(':name' => Language::get('Name')));
                                }
                            }
                        }
                        // ชื่อ
                        if (isset($save['name'])) {
                            if ($save['name'] == '') {
                                $ret['ret_register_name'] = 'Please fill in';
                            }
                        }
                        // เลขประจำตัวประชาชน 13 หลัก
                        if (!empty($save['idcard'])) {
                            if (!preg_match('/[0-9]{13,13}/', $save['idcard'])) {
                                $ret['ret_register_idcard'] = Language::replace('Invalid :name', array(':name' => Language::get('Identification No.')));
                            } else {
                                // ตรวจสอบโทรศัพท์ซ้ำ
                                $search = $db->first($user_table, array('idcard', $save['idcard']));
                                if ($search !== false && $user->id != $search->id) {
                                    $ret['ret_register_idcard'] = Language::replace('This :name already exist', array(':name' => Language::get('Identification No.')));
                                }
                            }
                        }
                        // แก้ไขรหัสผ่าน
                        if ($user->social == 0 && (!empty($password) || !empty($repassword))) {
                            if (mb_strlen($password) < 4) {
                                // รหัสผ่านต้องไม่น้อยกว่า 4 ตัวอักษร
                                $ret['ret_register_password'] = 'this';
                            } elseif ($repassword != $password) {
                                // ถ้าต้องการเปลี่ยนรหัสผ่าน กรุณากรอกรหัสผ่านสองช่องให้ตรงกัน
                                $ret['ret_register_repassword'] = 'this';
                            } else {
                                // password ใหม่ถูกต้อง
                                $save['salt'] = uniqid();
                                $save['password'] = sha1(self::$cfg->password_key.$password.$save['salt']);
                            }
                        }
                        if (empty($ret)) {
                            // อัปโหลดไฟล์
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file \Kotchasan\Http\UploadedFile */
                                if ($file->hasUploadFile()) {
                                    if (!File::makeDirectory(ROOT_PATH.self::$cfg->usericon_folder)) {
                                        // ไดเรคทอรี่ไม่สามารถสร้างได้
                                        $ret['ret_'.$item] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), self::$cfg->usericon_folder);
                                    } else {
                                        try {
                                            // อัปโหลด user icon
                                            $save['icon'] = $user->id.'.jpg';
                                            $file->cropImage(self::$cfg->user_icon_typies, ROOT_PATH.self::$cfg->usericon_folder.$save['icon'], self::$cfg->user_icon_w, self::$cfg->user_icon_h);
                                        } catch (\Exception $exc) {
                                            // ไม่สามารถอัปโหลดได้
                                            $ret['ret_'.$item] = Language::get($exc->getMessage());
                                        }
                                    }
                                } elseif ($file->hasError()) {
                                    // ข้อผิดพลาดการอัปโหลด
                                    $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                                }
                            }
                        }
                        if (!empty($save) && empty($ret)) {
                            // save
                            $db->update($user_table, $user->id, $save);
                            // เปลี่ยน password ที่ login ใหม่
                            if (!empty($save['password'])) {
                                $_SESSION['login']['password'] = $password;
                            }
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = 'reload';
                            // เคลียร์
                            $request->removeToken();
                        }
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
}
