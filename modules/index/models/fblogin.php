<?php
/**
 * @filesource modules/index/models/fblogin.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Fblogin;

use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 * Facebook Login
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รับข้อมูลที่ส่งมาจากการเข้าระบบด้วยบัญชี FB
     *
     * @param Request $request
     */
    public function chklogin(Request $request)
    {
        // session, token
        if ($request->initSession() && $request->isSafe()) {
            $ret = array();
            try {
                // สุ่มรหัสผ่านใหม่
                $password = uniqid();
                // ข้อมูลที่ส่งมา
                $save = array(
                    'displayname' => $request->post('first_name')->topic(),
                    'email' => $request->post('email')->url()
                );
                $fb_id = $request->post('id')->number();
                if (!Validator::email($save['email'])) {
                    $save['email'] = $fb_id;
                }
                if (empty($save['email'])) {
                    // ไม่มีอีเมล ของ Facebook
                    $ret['alert'] = Language::replace('Can not use :name account because :field is not available', array(':name' => Language::get('Facebook'), ':field' => Language::get('Email')));
                } else {
                    $save['name'] = trim($save['displayname'].' '.$request->post('last_name')->topic());
                    // db
                    $db = $this->db();
                    // table
                    $user_table = $this->getTableName('user');
                    // ตรวจสอบสมาชิกกับ db
                    $search = $db->createQuery()
                        ->from('user')
                        ->where(array('email', $save['email']), array('displayname', $save['displayname']), 'OR')
                        ->toArray()
                        ->first();
                    if ($search === false) {
                        // ยังไม่เคยลงทะเบียน, ลงทะเบียนใหม่
                        if (self::$cfg->demo_mode) {
                            // โหมดตัวอย่าง สามารถเข้าระบบหลังบ้านได้
                            $save['active'] = 1;
                            $save['permission'] = 'can_config';
                        } else {
                            $save['active'] = 0;
                            $save['permission'] = '';
                        }
                        $save['status'] = self::$cfg->new_register_status;
                        $save['id'] = $db->getNextId($this->getTableName('user'));
                        $save['social'] = 1;
                        $save['visited'] = 1;
                        $save['ip'] = $request->getClientIp();
                        $save['salt'] = uniqid();
                        $save['password'] = sha1(self::$cfg->password_key.$password.$save['salt']);
                        $save['lastvisited'] = time();
                        $save['create_date'] = $save['lastvisited'];
                        $save['icon'] = $save['id'].'.jpg';
                        $save['country'] = 'TH';
                        $save['token'] = sha1(self::$cfg->password_key.$password.$save['salt']);
                        $db->insert($user_table, $save);
                    } elseif ($save['email'] == $search['email'] && $search['social'] == 1) {
                        if ($search['ban'] == 0) {
                            // facebook เคยเยี่ยมชมแล้ว อัปเดตการเยี่ยมชม
                            $save = $search;
                            ++$save['visited'];
                            $save['lastvisited'] = time();
                            $save['ip'] = $request->getClientIp();
                            $save['salt'] = uniqid();
                            $save['token'] = sha1(self::$cfg->password_key.$password.$save['salt']);
                            // อัปเดต
                            $db->update($user_table, $search['id'], $save);
                        } else {
                            // สมาชิกถูกระงับการใช้งาน
                            $save = false;
                            $ret['alert'] = Language::get('Members were suspended');
                            $ret['isMember'] = 0;
                        }
                    } else {
                        // ไม่สามารถ login ได้ เนื่องจากมี email อยู่ก่อนแล้ว
                        $save = false;
                        $ret['alert'] = Language::replace('This :name already exist', array(':name' => Language::get('User')));
                        $ret['isMember'] = 0;
                    }
                    if (is_array($save)) {
                        if (!empty($fb_id)) {
                            // อัปเดต icon สมาชิก
                            $data = @file_get_contents('https://graph.facebook.com/'.$fb_id.'/picture');
                            if ($data) {
                                $f = @fopen(ROOT_PATH.self::$cfg->usericon_folder.$save['icon'], 'wb');
                                if ($f) {
                                    fwrite($f, $data);
                                    fclose($f);
                                }
                            }
                        }
                        // login
                        $save['permission'] = empty($save['permission']) ? array() : explode(',', trim($save['permission'], " \t\n\r\0\x0B,"));
                        unset($save['password']);
                        $_SESSION['login'] = $save;
                        // คืนค่า
                        $ret['action'] = $request->post('login_action')->toString();
                        $ret['alert'] = Language::replace('Welcome %s, login complete', array('%s' => $save['name']));
                        $ret['content'] = rawurlencode(createClass('Index\Login\View')->member($save));
                        // เคลียร์
                        $request->removeToken();
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
            // คืนค่าเป็น json
            echo json_encode($ret);
        }
    }
}
