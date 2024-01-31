<?php
/**
 * @filesource modules/index/models/sendmail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Sendmail;

use Gcms\Login;
use Kotchasan\Email;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * ส่งอีเมล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ส่งอีเมล ตาม ID
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            try {
                // ค่าที่ส่งมา
                $subject = $request->post('mail_subject')->topic();
                $detail = nl2br($request->post('mail_detail')->textarea());
                // ตรวจสอบ ค่าที่ส่งมา
                $reciever = array();
                foreach (self::getUser($request->post('mail_reciever')->filter('0-9a-z')) as $item) {
                    $reciever[] = empty($item['name']) ? $item['email'] : $item['name'].'<'.$item['email'].'>';
                }
                $reciever = implode(',', $reciever);
                // ตรวจสอบค่าที่ส่งมา
                if ($reciever == '') {
                    $ret['alert'] = Language::get('Unable to send e-mail, Because you can not send e-mail to yourself or can not find the email address of the recipient.');
                    $ret['location'] = WEB_URL.'index.php';
                } elseif ($subject == '') {
                    $ret['ret_mail_subject'] = 'Please fill in';
                } elseif ($detail == '') {
                    $ret['ret_mail_detail'] = 'Please fill in';
                } else {
                    // ส่งอีเมล
                    $replyto = empty($login['displayname']) ? $login['email'] : $login['displayname'].'<'.$login['email'].'>';
                    $err = Email::send($reciever, $replyto, $subject, $detail);
                    if (!$err->error()) {
                        // เคลียร์
                        $request->removeToken();
                        // ส่งอีเมลสำเร็จ
                        $ret['alert'] = Language::get('Your message was sent successfully');
                        $ret['location'] = WEB_URL.'index.php';
                    } else {
                        // ข้อผิดพลาดการส่งอีเมล
                        echo $err->getErrorMessage();
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
                echo $e->getMessage();
            }
        }
        if (!empty($ret)) {
            // คืนค่าเป็น JSON
            echo json_encode($ret);
        }
    }

    /**
     * อ่านข้อมูลสมาชิก สำหรับผู้รับจดหมาย
     * ไม่สามารถอ่านอีเมลตัวเองได้
     *
     * @param Request    $request
     * @param string|int $id      ข้อความ "admin" หรือ ID สมาชิกผู้รับ
     *
     * @return array ถ้าไม่พบคืนค่าแอเรย์ว่าง
     */
    public static function getUser($id)
    {
        $result = array();
        // สมาชิกเท่านั้น
        if (!empty($id) && $login = Login::isMember()) {
            $model = new static;
            $db = $model->db();
            $where = array();
            if ($id == 'admin') {
                $where[] = array('id', 'IN', $db->createQuery()->select('id')->from('user')->where(array('status', 1)));
            } else {
                $where[] = array('id', (int) $id);
            }
            $query = $db->createQuery()
                ->select('id', 'email', 'displayname')
                ->from('user')
                ->where($where)
                ->toArray()
                ->cacheOn();
            foreach ($query->execute() as $item) {
                if ($login['email'] != $item['email']) {
                    $result[$item['id']] = array(
                        'email' => $item['email'],
                        'name' => $item['displayname']
                    );
                }
            }
        }
        return $result;
    }
}
