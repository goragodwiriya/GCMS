<?php
/**
 * @filesource Widgets/Contact/Models/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Contact\Models;

use Kotchasan\Email;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 * Send email to admin
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * ส่งอีเมล ตาม ID
     *
     * @param Request $request
     */
    public function send(Request $request)
    {
        $ret = array();
        // session, token
        if ($request->initSession() && $request->isSafe()) {
            // ค่าที่ส่งมา
            $reciever = $request->post('mail_reciever')->topic();
            $sender = $request->post('mail_sender')->topic();
            $subject = $request->post('mail_subject')->topic();
            $detail = nl2br($request->post('mail_detail')->textarea());
            // ตรวจสอบ ค่าที่ส่งมา
            $tmp = $reciever;
            if (!empty($_SESSION['emails']) && !empty($tmp)) {
                foreach ($_SESSION['emails'] as $name => $email) {
                    if ($tmp == $name) {
                        $reciever = $name;
                        break;
                    }
                }
            }
            // ส่งหาแอดมิน
            if ($reciever == 'admin') {
                $query = $this->db()->createQuery()
                    ->select('email')
                    ->from('user')
                    ->where(array('status', 1))
                    ->cacheOn();
                $reciever = array();
                foreach ($query->execute() as $item) {
                    $reciever[] = $item->email;
                }
                $reciever = implode(',', $reciever);
            }
            // ตรวจสอบค่าที่ส่งมา
            if ($sender == '') {
                $ret['ret_mail_sender'] = 'Please fill in';
            } elseif (!Validator::email($sender)) {
                $ret['ret_mail_sender'] = 'Please fill in';
            } elseif ($reciever == '') {
                $ret['location'] = WEB_URL.'index.php';
            } elseif ($sender == $reciever) {
                $ret['ret_mail_sender'] = Language::get('Unable to send e-mail, Because you can not send e-mail to yourself or can not find the email address of the recipient.');
            } elseif ($subject == '') {
                $ret['ret_mail_subject'] = 'Please fill in';
            } elseif ($detail == '') {
                $ret['ret_mail_detail'] = 'Please fill in';
            } else {
                // ส่งอีเมล
                $err = Email::send($reciever, $sender, $subject, $detail);
                if (!$err->error()) {
                    // ส่งอีเมลสำเร็จ
                    $ret['alert'] = Language::get('Your message was sent successfully');
                    $ret['location'] = WEB_URL.'index.php';
                    // เคลียร์
                    $request->removeToken();
                } else {
                    // ข้อผิดพลาดการส่งอีเมล
                    echo $err->getErrorMessage();
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
