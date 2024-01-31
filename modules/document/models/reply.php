<?php
/**
 * @filesource modules/document/models/reply.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Reply;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 * module=document-reply
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บันทึกความคิดเห็น
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token
        if ($request->initSession() && $request->isSafe()) {
            // login
            $login = Login::isMember();
            if ($login && $login['email'] == 'demo') {
                $ret['alert'] = Language::get('Unable to complete the transaction');
            } else {
                try {
                    // ค่าที่ส่งมา
                    $post = array(
                        'detail' => $request->post('reply_detail')->textarea()
                    );
                    $index_id = $request->post('index_id')->toInt();
                    $id = $request->post('reply_id')->toInt();
                    // ตรวจสอบค่าที่ส่งมา
                    $index = $this->get($id, $request->post('module_id')->toInt(), $index_id);
                    if ($index && $index->canReply) {
                        // true = guest โพสต์ได้
                        $guest = in_array(-1, $index->can_reply);
                        // ผู้ดูแล
                        $moderator = Gcms::canConfig($login, $index, 'moderator');
                        if ($post['detail'] == '') {
                            // ไม่ได้กรอกรายละเอียด
                            $ret['ret_reply_detail'] = 'Please fill in';
                        } elseif ($id == 0) {
                            // คอลัมน์ที่เป็น username
                            $field = self::$cfg->login_fields[0];
                            // ใหม่ ตรวจสอบการ login
                            if ($login) {
                                // login ใช้ข้อมูลของคน login
                                $post['member_id'] = $login['id'];
                                $post['email'] = $login[$field];
                                $post['sender'] = empty($login['displayname']) ? $login[$field] : $login['displayname'];
                            } else {
                                // ตรวจสอบการ login
                                $username = $request->post('reply_email')->url();
                                $password = $request->post('reply_password')->password();
                                if ($username == '') {
                                    // ไม่ได้กรอก username
                                    $ret['ret_reply_email'] = 'Please fill in';
                                }
                                if ($password == '' && !$guest) {
                                    // สมาชิกเท่านั้น และ ไม่ได้กรอกรหัสผ่าน
                                    $ret['ret_reply_password'] = 'Please fill in';
                                }
                                if ($username != '' && $password != '') {
                                    // ตรวจสอบ username และ password
                                    $user = Login::checkMember(array(
                                        'username' => $username,
                                        'password' => $password
                                    ));
                                    if (is_string($user)) {
                                        if (Login::$login_input == 'password') {
                                            $ret['ret_reply_password'] = $user;
                                        } elseif ($request->post('reply_email')->exists()) {
                                            $ret['ret_reply_email'] = $user;
                                        } else {
                                            $ret['ret_reply_email'] = $user;
                                        }
                                    } elseif (!in_array($user['status'], $index->can_reply)) {
                                        // ไม่สามารถแสดงความคิดเห็นได้
                                        $ret['alert'] = Language::get('Sorry, you do not have permission to comment');
                                    } else {
                                        // สมาชิก สามารถแสดงความคิดเห็นได้
                                        $post['member_id'] = $user['id'];
                                        $post['email'] = $username;
                                        $post['sender'] = empty($user['displayname']) ? $username : $user['displayname'];
                                    }
                                } elseif ($guest) {
                                    // ตรวจสอบ user ซ้ำกับสมาชิก สำหรับบุคคลทั่วไป
                                    $search = $this->db()->createQuery()
                                        ->from('user')
                                        ->where(array($field, $username))
                                        ->first('id');
                                    if ($search) {
                                        // พบ username ต้องการ password
                                        $ret['ret_reply_password'] = 'Please fill in';
                                    } elseif ($field === 'email' && !Validator::email($username)) {
                                        // email ไม่ถูกต้อง
                                        $ret['ret_reply_email'] = Language::replace('Invalid :name', array(':name' => Language::get('Email')));
                                    } else {
                                        // guest
                                        $post['member_id'] = 0;
                                        $post['email'] = $username;
                                        $post['sender'] = $username;
                                    }
                                } else {
                                    // สมาชิกเท่านั้น
                                    $ret['alert'] = Language::get('Members Only');
                                }
                            }
                        } elseif (!($login && ($index->member_id == $login['id'] || $moderator))) {
                            // แก้ไข ไม่ใช่เจ้าของ และ ไม่ใช่ผู้ดูแล
                            $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
                        }
                        if ($id == 0 && empty($ret) && $post['detail'] != '') {
                            // ตรวจสอบโพสต์ซ้ำภายใน 1 วัน
                            $search = $this->db()->createQuery()
                                ->from('comment')
                                ->where(array(
                                    array('detail', $post['detail']),
                                    array('email', $post['email']),
                                    array('module_id', $index->module_id),
                                    array('last_update', '>', time() - 86400)
                                ))
                                ->first('id');
                            if ($search) {
                                $ret['alert'] = Language::get('Your post is already exists. You do not need to post this.');
                            }
                        }
                        // เวลาปัจจุบัน
                        $mktime = time();
                        if (empty($ret)) {
                            $post['last_update'] = $mktime;
                            if ($id > 0) {
                                // แก้ไข
                                $this->db()->update($this->getTableName('comment'), $id, $post);
                                // คืนค่า
                                $ret['alert'] = Language::get('Edit comment successfully');
                            } else {
                                // ใหม่
                                $post['ip'] = $request->getClientIp();
                                $post['index_id'] = $index->id;
                                $post['module_id'] = $index->module_id;
                                $id = $this->db()->insert($this->getTableName('comment'), $post);
                                // อัปเดตคำถาม
                                $q['commentator'] = empty($post['sender']) ? $post['email'] : $post['sender'];
                                $q['commentator_id'] = $post['member_id'];
                                $q['comments'] = $index->comments + 1;
                                $q['comment_id'] = $id;
                                // อัปเดตสมาชิก
                                if ($post['member_id'] > 0) {
                                    $this->db()->createQuery()->update('user')->set('`reply`=`reply`+1')->where($post['member_id'])->execute();
                                }
                                if ($index->category_id > 0) {
                                    // อัปเดตจำนวนเรื่อง และ ความคิดเห็น ในหมวด
                                    \Document\Admin\Write\Model::updateCategories((int) $index->module_id);
                                }
                                // คืนค่า
                                $ret['alert'] = Language::get('Thank you for your comment');
                            }
                            // เคลียร์
                            $request->removeToken();
                            // reload
                            $location = WEB_URL.'index.php?module='.$index->module.'&id='.$index_id.'&visited='.$mktime;
                            // ส่งข้อความแจ้งเตือนไปยังไลน์เมื่อมีความคิดเห็นใหม่
                            $line_notifications = !empty($index->line_notifications) && in_array(3, $index->line_notifications);
                            $line_uid = !empty($index->line_uid);
                            if ($line_notifications || $line_uid) {
                                $msg = Language::get('DOCUMENT_NOTIFICATIONS');
                                $msg = implode("\n", array(
                                    (isset($post['sender']) ? $post['sender'].' ' : '').$msg[3].':',
                                    $post['detail'],
                                    $location.'&openExternalBrowser=1#R_'.$id
                                ));
                            }
                            if ($line_notifications) {
                                \Gcms\Line::send($msg);
                            }
                            if ($line_uid && $index->writer_id != $post['member_id']) {
                                \Gcms\Line::sendTo($index->line_uid, $msg);
                            }
                            $location .= self::$cfg->use_ajax == 1 ? "&to=R_$id" : "#R_$id";
                            $ret['location'] = $location;
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * อ่านข้อมูล ความคิดเห็น
     *
     * @param int $id        ID ของความคิดเห็น, ถ้าเป็นความคิดเห็นใหม่
     * @param int $module_id ID ของโมดูล
     * @param int $index_id  ID ของคำถาม
     *
     * @return object|bool คืนค่าผลลัพท์ที่พบ (Object) ไม่พบข้อมูลคืนค่า false
     */
    private function get($id, $module_id, $index_id)
    {
        if ($id > 0) {
            // แก้ไข
            $index = $this->db()->createQuery()
                ->from('comment R')
                ->join('index Q', 'INNER', array(array('Q.id', 'R.index_id'), array('Q.index', 0)))
                ->join('modules M', 'INNER', array('M.id', 'Q.module_id'))
                ->join('category C', 'LEFT', array(array('C.module_id', 'Q.module_id'), array('C.category_id', 'Q.category_id')))
                ->join('user U', 'LEFT', array('U.id', 'Q.member_id'))
                ->where(array(array('R.id', $id), array('R.index_id', $index_id), array('R.module_id', $module_id)))
                ->toArray()
                ->cacheOn()
                ->first('R.member_id', 'Q.member_id writer_id', 'Q.id', 'Q.comments', 'Q.module_id', 'Q.can_reply canReply', 'Q.alias', 'M.module', 'M.config', 'C.category_id');
        } else {
            // ใหม่
            $index = $this->db()->createQuery()
                ->from('index Q')
                ->join('modules M', 'INNER', array('M.id', 'Q.module_id'))
                ->join('category C', 'LEFT', array(array('C.module_id', 'Q.module_id'), array('C.category_id', 'Q.category_id')))
                ->where(array(array('Q.id', $index_id), array('Q.module_id', $module_id)))
                ->toArray()
                ->cacheOn()
                ->first('0 member_id', 'Q.member_id writer_id', 'Q.id', 'Q.comments', 'Q.module_id', 'Q.can_reply canReply', 'Q.alias', 'M.module', 'M.config', 'C.category_id');
        }
        if ($index) {
            // config จากโมดูล
            $index = ArrayTool::unserialize($index['config'], $index);
            unset($index['config']);
            return (object) $index;
        }
        return false;
    }
}
