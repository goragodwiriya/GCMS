<?php
/**
 * @filesource modules/index/models/mailwrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mailwrite;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Validator;

/**
 * module=mailwrite
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านอีเมลที่แก้ไข
     * id = 0 สร้างอีเมลใหม่
     *
     * @param int $id
     *
     * @return object|bool คืนค่าข้อมูล object ไม่พบคืนค่า false
     */
    public static function getIndex($id)
    {
        if (is_int($id)) {
            if (empty($id)) {
                $index = (object) array(
                    'id' => 0,
                    'from_email' => '',
                    'copy_to' => '',
                    'subject' => '',
                    'language' => Language::name(),
                    'detail' => Template::load('', '', 'mailtemplate'),
                    'name' => '',
                    'module' => 'mailmerge'
                );
            } else {
                $model = new static;
                $index = $model->db()->first($model->getTableName('emailtemplate'), array('id', $id));
            }
            return $index;
        }
        return false;
    }

    /**
     * รับค่าจากฟอร์ม (mailwrite.php)
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
                    $model = new static;
                    $table_email = $model->getTableName('emailtemplate');
                    // รับค่าจากการ POST
                    $save = array(
                        'from_email' => $request->post('from_email')->url(),
                        'copy_to' => $request->post('copy_to')->url(),
                        'subject' => $request->post('subject')->topic(),
                        'language' => $request->post('language')->text(),
                        'detail' => $request->post('detail')->toString(),
                        'module' => $request->post('module')->topic()
                    );
                    $id = $request->post('id')->toInt();
                    // ตรวจสอบค่าที่ส่งมา
                    if (!empty($id)) {
                        $email = $model->db()->first($table_email, array('id', $id));
                    }
                    // มีการแก้ไขภาษา ตรวจสอบว่ามีรายการในภาษาที่เลือกหรือไม่
                    if (!empty($id) && $save['language'] != $email->language) {
                        $where = array(
                            array('email_id', $email->email_id),
                            array('module', $email->module),
                            array('language', $save['language'])
                        );
                        $search = $model->db()->first($table_email, $where);
                        if ($search === false) {
                            // บันทึกเป็นรายการใหม่
                            $id = 0;
                        } else {
                            // มีอีเมลในภาษาที่เลือกอยู่แล้ว
                            $ret['ret_language'] = Language::get('This entry is in selected language');
                        }
                    }
                    // from_email
                    if (!empty($save['from_email']) && !Validator::email($save['from_email'])) {
                        $ret['ret_from_email'] = 'this';
                    }
                    // copy_to
                    if (!empty($save['copy_to'])) {
                        foreach (explode(',', $save['copy_to']) as $item) {
                            if (!Validator::email($item)) {
                                if (empty($ret)) {
                                    $ret['ret_copy_to'] = 'this';
                                    break;
                                }
                            }
                        }
                    }
                    // subject
                    if (empty($save['subject'])) {
                        $ret['ret_subject'] = 'Please fill in';
                    }
                    // detail
                    $patt = array(
                        '/^(&nbsp;|\s){0,}<br[\s\/]+?>(&nbsp;|\s){0,}$/iu' => '',
                        '/<\?(.*?)\?>/su' => '',
                        '@<script[^>]*?>.*?</script>@siu' => ''
                    );
                    $save['detail'] = trim(preg_replace(array_keys($patt), array_values($patt), $save['detail']));
                    $save['last_update'] = time();
                    if (empty($ret)) {
                        if (empty($id)) {
                            // ใหม่
                            $save['name'] = $email->name;
                            $save['email_id'] = $email->email_id;
                            $save['module'] = $email->module;
                            $model->db()->insert($table_email, $save);
                        } else {
                            // แก้ไข
                            $model->db()->update($table_email, $id, $save);
                        }
                        // ส่งค่ากลับ
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'mailtemplate', 'id' => 0));
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
}
