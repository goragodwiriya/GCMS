<?php
/**
 * @filesource modules/index/models/editprofile.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Editprofile;

use Gcms\Login;
use Kotchasan\ArrayTool;
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
     * อ่านข้อมูลสมาชิกที่ $id
     * คืนค่าข้อมูล array ไม่พบคืนค่า false
     *
     * @param int $id
     *
     * @return array|bool
     */
    public static function get($id)
    {
        if (!empty($id)) {
            $user = static::createQuery()
                ->from('user')
                ->where(array('id', $id))
                ->toArray()
                ->first();
            if ($user) {
                // permission
                $user['permission'] = empty($user['permission']) ? array() : explode(',', trim($user['permission'], " \t\n\r\0\x0B,"));
                return $user;
            }
        }
        return false;
    }

    /**
     * แก้ไขข้อมูลสมาชิก (editprofile.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, แอดมิน และไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = array(
                        'email' => $request->post('register_email')->url(),
                        'displayname' => $request->post('register_displayname')->topic(),
                        'sex' => $request->post('register_sex')->filter('a-z'),
                        'website' => $request->post('register_website')->url(),
                        'name' => $request->post('register_name')->topic(),
                        'idcard' => $request->post('register_idcard')->number(),
                        'company' => $request->post('register_company')->topic(),
                        'phone1' => $request->post('register_phone1')->number(),
                        'phone2' => $request->post('register_phone2')->number(),
                        'address1' => $request->post('register_address1')->topic(),
                        'address2' => $request->post('register_address2')->topic(),
                        'provinceID' => $request->post('register_provinceID')->number(),
                        'province' => $request->post('register_province')->topic(),
                        'zipcode' => $request->post('register_zipcode')->number(),
                        'country' => $request->post('register_country')->topic(),
                        'status' => $request->post('register_status')->toInt(),
                        'birthday' => $request->post('register_birthday')->date()
                    );
                    $permission = $request->post('register_permission', array())->topic();
                    // ชื่อตาราง user
                    $table_user = $this->getTableName('user');
                    // database connection
                    $db = $this->db();
                    // ตรวจสอบค่าที่ส่งมา
                    $user = $db->first($table_user, $request->post('register_id')->toInt());
                    // ตัวเอง, แอดมินแก้ไขได้ทุกคน ยกเว้น ID 1
                    if ($user && ($user->id == $login['id'] || ($user->id > 1 && $login['status'] == 1))) {
                        // แอดมิน สามารถแก้ไขได้ทุกอย่าง
                        $isAdmin = $login['status'] == 1;
                        // ไม่ใช่แอดมิน ใช้อีเมลเดิมจากฐานข้อมูล
                        if (!$isAdmin && $user->id > 0) {
                            $save['email'] = $user->email;
                            $save['phone1'] = $user->phone1;
                        }
                        // ตรวจสอบค่าที่ส่งมา
                        $requirePassword = false;
                        // อีเมล
                        if (empty($save['email'])) {
                            $ret['ret_register_email'] = 'Please fill in';
                        } else {
                            // ตรวจสอบอีเมลซ้ำ
                            $search = $db->first($table_user, array('email', $save['email']));
                            if ($search !== false && $user->id != $search->id) {
                                $ret['ret_register_email'] = Language::replace('This :name already exist', array(':name' => Language::get('Email')));
                            } else {
                                $requirePassword = $user->email !== $save['email'];
                            }
                        }
                        // ชื่อเรียก
                        if (mb_strlen($save['displayname']) < 2) {
                            $ret['ret_register_displayname'] = Language::get('Name for the show on the site at least 2 characters');
                        } elseif (in_array($save['displayname'], self::$cfg->member_reserv)) {
                            $ret['ret_register_displayname'] = Language::replace('Cannot use :name', array(':name' => Language::get('Name')));
                        } else {
                            // ตรวจสอบ displayname ซ้ำ
                            $search = $db->first($table_user, array('displayname', $save['displayname']));
                            if ($search !== false && $user->id != $search->id) {
                                $ret['ret_register_displayname'] = Language::replace('This :name already exist', array(':name' => Language::get('Name')));
                            }
                        }
                        // ชื่อ
                        if ($save['name'] == '') {
                            $ret['ret_register_name'] = 'Please fill in';
                        }
                        // โทรศัพท์
                        if (!empty($save['phone1'])) {
                            if (!preg_match('/[0-9]{9,10}/', $save['phone1'])) {
                                $ret['ret_register_phone1'] = Language::replace('Invalid :name', array(':name' => Language::get('Phone number')));
                            } else {
                                // ตรวจสอบโทรศัพท์ซ้ำ
                                $search = $db->first($table_user, array('phone1', $save['phone1']));
                                if ($search !== false && $user->id != $search->id) {
                                    $ret['ret_register_phone1'] = Language::replace('This :name already exist', array(':name' => Language::get('Phone number')));
                                }
                            }
                        } elseif (self::$cfg->member_phone == 2) {
                            // ไม่ได้กรอก phone1
                            $ret['ret_register_phone1'] = 'Please fill in';
                        }
                        // เลขประจำตัวประชาชน 13 หลัก
                        if (!empty($save['idcard'])) {
                            if (!preg_match('/[0-9]{13,13}/', $save['idcard'])) {
                                $ret['ret_register_idcard'] = Language::replace('Invalid :name', array(':name' => Language::get('Identification No.')));
                            } else {
                                // ตรวจสอบโทรศัพท์ซ้ำ
                                $search = $db->first($table_user, array('idcard', $save['idcard']));
                                if ($search !== false && $user->id != $search->id) {
                                    $ret['ret_register_idcard'] = Language::replace('This :name already exist', array(':name' => Language::get('Identification No.')));
                                }
                            }
                        }
                        // แก้ไขรหัสผ่าน
                        $password = $request->post('register_password')->password();
                        $repassword = $request->post('register_repassword')->password();
                        if (!empty($password) || !empty($repassword)) {
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
                                $requirePassword = false;
                            }
                        }
                        // มีการเปลี่ยน email ต้องการรหัสผ่าน
                        if (empty($ret) && $requirePassword) {
                            $ret['ret_register_password'] = 'this';
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
                        if (empty($ret)) {
                            if ($save['country'] == 'TH') {
                                // จังหวัดจาก provinceID ถ้าเลือกประเทศไทย
                                $save['province'] = \Kotchasan\Province::get($save['provinceID']);
                            }
                            if ($login['status'] == 1 && $user->id != 1 && $login['id'] != $user->id) {
                                // แอดมินแต่ไม่ใช่ตัวเองและแก้ไขสมาชิกสถานะอื่น
                                $save['permission'] = empty($permission) ? '' : ','.implode(',', $permission).',';
                            } else {
                                // ตัวเอง ห้ามแก้ไข
                                unset($save['status']);
                                unset($save['point']);
                            }
                            if (!empty($user->social)) {
                                // social ห้ามแก้ไข
                                unset($save['email']);
                                unset($save['password']);
                            }
                            // บันทึก
                            $db->update($table_user, $user->id, $save);
                            if ($login['id'] == $user->id) {
                                // ตัวเอง อัปเดตข้อมูลการ login
                                $_SESSION['login'] = ArrayTool::replace($_SESSION['login'], $save);
                                // reload หน้าเว็บ
                                $ret['location'] = 'reload';
                            } else {
                                // กลับไปหน้าก่อนหน้า
                                $ret['location'] = $request->getUri()->postBack('index.php', array('id' => null));
                            }
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
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
