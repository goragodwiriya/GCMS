<?php
/**
 * @filesource modules/index/models/member.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Member;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=member
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลสำหรับใส่ลงในตาราง
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('id', 'name', 'ban', 'active', 'social', 'displayname', 'email', 'phone1', 'status', 'create_date', 'lastvisited', 'visited', 'website')
            ->from('user');
    }

    /**
     * อ่านข้อมูลสมาชิกที่ $user_id
     *
     * @param int $user_id
     *
     * @return object|null คืนค่า object ของข้อมูล ไม่พบคืนค่า null
     */
    public static function get($user_id)
    {
        $array = array(
            'U.id',
            'U.name',
            'U.email',
            'U.displayname',
            'U.website',
            'U.company',
            'U.address1',
            'U.address2',
            'U.phone1',
            'U.phone2',
            'U.sex',
            'U.birthday',
            'U.zipcode',
            'U.country',
            'U.provinceID',
            'U.province',
            'U.status',
            'U.action',
            'U.icon',
            'U.social'
        );
        return static::createQuery()
            ->from('user U')
            ->where(array('U.id', $user_id))
            ->first($array);
    }

    /**
     * ตารางสมาชิก (member.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, admin, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isAdmin()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    // ตาราง user
                    $user_table = $this->getTableName('user');
                    if ($action === 'delete') {
                        // ลบไอคอนสมาชิก
                        $query = $this->db()->createQuery()
                            ->select('icon')
                            ->from('user')
                            ->where(array(
                                array('id', $match[1]),
                                array('id', '!=', 1),
                                array('icon', '!=', '')
                            ))
                            ->toArray();
                        foreach ($query->execute() as $item) {
                            @unlink(ROOT_PATH.self::$cfg->usericon_folder.$item['icon']);
                        }
                        // ลบสมาชิก
                        $this->db()->delete($user_table, array(
                            array('id', $match[1]),
                            array('id', '!=', 1)
                        ), 0);
                        // ไดเร็คทอรี่ที่ติดตั้งโมดูล
                        $dir = ROOT_PATH.'modules/';
                        // ส่วนเสริมที่ติดตั้ง
                        $f = @opendir($dir);
                        if ($f) {
                            while (false !== ($owner = readdir($f))) {
                                if ($owner != '.' && $owner != '..' && $owner != 'js' && $owner != 'css' && $owner != 'index') {
                                    if (file_exists($dir.$owner.'/models/admin/member.php')) {
                                        include $dir.$owner.'/models/admin/member.php';
                                        $class = ucfirst($owner).'\Admin\Member\Model';
                                        if (method_exists($class, 'delete')) {
                                            // แจ้งลบสมาชิกไปยังโมดูลต่างๆ
                                            $class::delete($this, $match[1]);
                                        }
                                    }
                                }
                            }
                            closedir($f);
                        }
                        // คืนค่า
                        $ret['location'] = 'reload';
                    } elseif ($action === 'accept') {
                        // ยอมรับสมาชิกที่เลือก
                        $this->db()->update($user_table, array(
                            array('id', $match[1]),
                            array('social', 0)
                        ), array(
                            'activatecode' => ''
                        ));
                        // คืนค่า
                        $ret['location'] = 'reload';
                    } elseif (preg_match('/(ban|active)_([01]{1,1})/', $action, $match2)) {
                        // update ban,active
                        $this->db()->update($user_table, array(
                            array('id', $match[1]),
                            array('id', '!=', 1)
                        ), array($match2[1] => $match2[2]));
                        // คืนค่า
                        $ret['location'] = 'reload';
                    } elseif ($action === 'activate' || $action === 'sendpassword') {
                        // ขอรหัสผ่านใหม่ ส่งอีเมลยืนยันสมาชิก
                        $query = $this->db()->createQuery()
                            ->select('id', 'email', 'activatecode', 'phone1')
                            ->from('user')
                            ->where(array(
                                array('id', $match[1]),
                                array('id', '!=', 1),
                                array('social', 0)
                            ))
                            ->toArray();
                        $msgs = array();
                        foreach ($query->execute() as $item) {
                            // รหัสผ่านใหม่
                            $password = substr(uniqid(), -6);
                            $salt = uniqid();
                            $save = array(
                                'salt' => $salt,
                                'password' => sha1(self::$cfg->password_key.$password.$salt)
                            );
                            // ข้อมูลอีเมล
                            $replace = array(
                                '/%PASSWORD%/' => $password,
                                '/%EMAIL%/' => $item['email']
                            );
                            if ($action === 'activate' || !empty($item['activatecode'])) {
                                // activate หรือ ยังไม่ได้ activate
                                $save['activatecode'] = empty($item['activatecode']) ? md5(uniqid()) : $item['activatecode'];
                                $replace['/%ID%/'] = $save['activatecode'];
                                // send mail
                                $err = \Gcms\Email::send(1, 'member', $replace, $item['email']);
                            } else {
                                // send mail
                                $err = \Gcms\Email::send(3, 'member', $replace, $item['email']);
                            }
                            if ($err->error()) {
                                $msgs[] = $err->getErrorMessage();
                            } else {
                                // อัปเดตรหัสผ่านใหม่
                                $this->db()->update($user_table, $item['id'], $save);
                            }
                        }
                        if (empty($msgs)) {
                            // ส่งอีเมล สำเร็จ
                            $ret['alert'] = Language::get('Your message was sent successfully');
                        } else {
                            // มีข้อผิดพลาด
                            $ret['alert'] = implode("\n", $msgs);
                        }
                        // คืนค่า
                        $ret['location'] = 'reload';
                    } elseif ($request->post('module')->toString() === 'status') {
                        // เปลี่ยนสถานะสมาชิก
                        $this->db()->update($user_table, array(
                            array('id', $match[1]),
                            array('id', '!=', 1),
                            array('social', 0)
                        ), array(
                            'status' => (int) $action
                        ));
                        // คืนค่า
                        $ret['location'] = 'reload';
                    }
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
