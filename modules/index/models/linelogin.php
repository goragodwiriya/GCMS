<?php
/**
 * @filesource modules/index/models/linelogin.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Linelogin;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * LINE Login
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รับข้อมูลที่ส่งมาจากการเข้าระบบด้วยบัญชี LINE
     *
     * @param Request $request
     * @param array $user
     *
     * @return array|string สำเร็จคืนค่า Array ข้อมูลสมาชิก ไม่สำเร็จคืนค่าข้อความผิดพลาด
     */
    public static function chklogin(Request $request, $user)
    {
        $save = array(
            'email' => empty($user['email']) ? $user['sub'] : $user['email'],
            'name' => $user['name'],
            'line_uid' => $user['sub']
        );
        if (preg_match('/^(.*)[\s]+(.*)$/', $user['name'], $match)) {
            $save['displayname'] = $match[1];
            $save['fname'] = $match[1];
            $save['lname'] = $match[2];
        } else {
            $save['displayname'] = $user['name'];
        }
        // สุ่มรหัสผ่านใหม่
        $password = uniqid();
        // Model
        $model = static::create();
        // db
        $db = $model->db();
        // table
        $user_table = $model->getTableName('user');
        // ตรวจสอบสมาชิกกับ db
        $search = $db->createQuery()
            ->from('user')
            ->where(array(
                array('email', $save['email']),
                array('displayname', $save['displayname'])
            ), 'OR')
            ->toArray()
            ->first();
        if ($search === false) {
            // ยังไม่เคยลงทะเบียน, ลงทะเบียนใหม่
            $save['active'] = 0;
            $save['permission'] = '';
            $save['status'] = self::$cfg->new_register_status;
            $save['id'] = $db->getNextId($user_table);
            $save['social'] = 3;
            $save['visited'] = 1;
            $save['ip'] = $request->getClientIp();
            $save['salt'] = uniqid();
            $save['password'] = sha1($password.$save['salt']);
            $save['lastvisited'] = time();
            $save['create_date'] = $save['lastvisited'];
            $save['icon'] = $save['id'].'.jpg';
            $save['country'] = 'TH';
            $save['token'] = \Kotchasan\Password::uniqid(40);
            $db->insert($user_table, $save);
        } elseif ($search['social'] == 3) {
            // สมาชิก Line
            if ($search['ban'] == 0) {
                // เคยเยี่ยมชมแล้ว อัปเดตการเยี่ยมชม
                $save = $search;
                ++$save['visited'];
                $save['lastvisited'] = time();
                $save['ip'] = $request->getClientIp();
                $save['token'] = \Kotchasan\Password::uniqid(40);
                $save['line_uid'] = $user['sub'];
                // อัปเดต
                $db->update($user_table, $search['id'], $save);
            } else {
                // สมาชิกถูกระงับการใช้งาน
                $save = Language::get('Members were suspended');
            }
        } else {
            if ($save['email'] == $search['email']) {
                // อัปเดตสมาชิกถ้า username ตรงกันกับบัญชีไลน์
                $db->update($user_table, $search['id'], array(
                    'line_uid' => $user['sub']
                ));
            }
            // ไม่สามารถ login ได้ เนื่องจากมี email อยู่ก่อนแล้ว
            $save = Language::replace('This :name already exist', array(':name' => Language::get('Username')));
        }
        if (is_array($save)) {
            if (!empty($user['picture'])) {
                // อัปเดต icon สมาชิก
                $data = @file_get_contents($user['picture']);
                if ($data) {
                    $f = @fopen(ROOT_PATH.self::$cfg->usericon_folder.$save['icon'], 'wb');
                    if ($f) {
                        fwrite($f, $data);
                        fclose($f);
                    }
                }
            }
            // ส่งข้อความ ยินดีต้อนรับ
            $message = Language::replace('Welcome %s, login complete', array('%s' => $save['name']));
            \Gcms\Line::sendTo($save['email'], $message);
        }
        return $save;
    }

    /**
     * คืนค่า URL สำหรับการเข้าระบบด้วย LINE
     *
     * @param string $ret_url
     *
     * @return string
     */
    public static function url($ret_url)
    {
        $params = array(
            'response_type' => 'code',
            'client_id' => self::$cfg->line_channel_id,
            'redirect_uri' => str_replace('www.', '', WEB_URL.'line/callback.php'),
            'state' => base64_encode($ret_url),
            'scope' => 'profile openid email',
            'nonce' => uniqid(),
            'openExternalBrowser' => 1
        );
        return 'https://access.line.me/oauth2/v2.1/authorize?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    }
}
