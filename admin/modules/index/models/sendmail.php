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
use Kotchasan\Validator;

/**
 * ส่งอีเมล (admin)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ลิสต์รายชื่ออีเมลของแอดมิน
     */
    public static function findAdmin()
    {
        $model = new static;
        $result = array();
        foreach ($model->db()->select($model->getTableName('user'), array('status', 1), array('email')) as $item) {
            $result[] = $item['email'];
        }
        return $result;
    }

    /**
     * form submit (sendmail.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = array(
                        'reciever' => $request->post('reciever')->topic(),
                        'from' => $request->post('from')->topic(),
                        'subject' => $request->post('subject')->topic(),
                        'detail' => $request->post('detail')->detail()
                    );
                    // reciever
                    if (empty($save['reciever'])) {
                        $ret['ret_reciever'] = 'Please fill in';
                    } else {
                        foreach (explode(',', $save['reciever']) as $item) {
                            if (!Validator::email($item)) {
                                if (empty($ret)) {
                                    $ret['ret_reciever'] = 'Please fill in';
                                    break;
                                }
                            }
                        }
                    }
                    // subject
                    if (empty($save['subject'])) {
                        $ret['ret_subject'] = 'Please fill in';
                    }
                    // from
                    if (Login::isAdmin()) {
                        if ($save['from'] == self::$cfg->noreply_email) {
                            $save['from'] = strip_tags(self::$cfg->web_title).'<'.self::$cfg->noreply_email.'>';
                        } else {
                            $user = $this->db()->createQuery()
                                ->from('user')
                                ->where(array('email', $save['from']))
                                ->first('email', 'displayname');
                            if ($user) {
                                $save['from'] = empty($user->displayname) ? $user->email : $user->displayname.'<'.$user->email.'>';
                            } else {
                                // ไม่พบผู้ส่ง ให้ส่งโดยตัวเอง
                                $save['from'] = $login['email'];
                            }
                        }
                    } else {
                        // ไม่ใช่แอดมิน ผู้ส่งเป็นตัวเองเท่านั้น
                        $save['from'] = $login['email'];
                    }
                    // detail
                    $patt = array(
                        '/^(&nbsp;|\s){0,}<br[\s\/]+?>(&nbsp;|\s){0,}$/iu' => '',
                        '/<\?(.*?)\?>/su' => '',
                        '@<script[^>]*?>.*?</script>@siu' => ''
                    );
                    $save['detail'] = trim(preg_replace(array_keys($patt), array_values($patt), $save['detail']));
                    if (empty($ret)) {
                        $err = Email::send($save['reciever'], $save['from'], $save['subject'], $save['detail']);
                        if (!$err->error()) {
                            // ส่งอีเมลสำเร็จ
                            $ret['alert'] = Language::get('Your message was sent successfully');
                            $ret['location'] = 'reload';
                            // เคลียร์
                            $request->removeToken();
                        } else {
                            // ข้อผิดพลาดการส่งอีเมล
                            $ret['alert'] = $err->getErrorMessage();
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
