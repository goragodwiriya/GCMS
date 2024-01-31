<?php
/**
 * @filesource modules/board/models/reply.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Reply;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\ArrayTool;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Validator;

/**
 *  module=board-reply
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
                    if ($index) {
                        // true = guest โพสต์ได้
                        $guest = in_array(-1, $index->can_reply);
                        // ผู้ดูแล
                        $moderator = Gcms::canConfig($login, $index, 'moderator');
                        // คอลัมน์ที่เป็น username
                        $field = self::$cfg->login_fields[0];
                        // รายการไฟล์อัปโหลด
                        $fileUpload = array();
                        if (empty($index->img_upload_type)) {
                            // ไม่สามารถอัปโหลดได้ ต้องมีรายละเอียด
                            $requireDetail = true;
                        } else {
                            // ต้องมีรายละเอียด ถ้าเป็นโพสต์ใหม่ หรือ แก้ไขและไม่มีรูป
                            $requireDetail = ($id == 0 || ($id > 0 && empty($index->picture)));
                            foreach ($request->getUploadedFiles() as $item => $file) {
                                /* @var $file \Kotchasan\Http\UploadedFile */
                                if ($file->hasUploadFile()) {
                                    $fileUpload[$item] = $file;
                                    // ไม่ต้องมีรายละเอียด ถ้ามีการอัปโหลดรูปภาพมาด้วย
                                    $requireDetail = false;
                                }
                            }
                        }
                        if ($index->locked == 1 && !$moderator) {
                            // บอร์ด lock (ผู้ดูแลสามารถ post ได้)
                            $ret['alert'] = Language::get('Sorry, can not be processed. Because the topic is closed');
                        } elseif (!empty($fileUpload) && !File::makeDirectory(ROOT_PATH.DATA_FOLDER.'board/')) {
                            // ไดเรคทอรี่ไม่สามารถสร้างได้
                            $ret['alert'] = sprintf(Language::get('Directory %s cannot be created or is read-only.'), DATA_FOLDER.'board/');
                        } elseif ($post['detail'] == '' && $requireDetail) {
                            // ไม่ได้กรอกรายละเอียด และ ไม่มีรูป
                            $ret['ret_reply_detail'] = Language::get('Please fill in').' '.Language::get('Detail');
                        } elseif ($id == 0) {
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
                                        $post['email'] = $user[$field];
                                        $post['sender'] = empty($user['displayname']) ? $user[$field] : $user['displayname'];
                                    }
                                } elseif ($guest) {
                                    // ตรวจสอบ username ซ้ำกับสมาชิก สำหรับบุคคลทั่วไป
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
                                ->from('board_r')
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
                        // ไฟล์อัปโหลด
                        if (empty($ret) && !empty($index->img_upload_type)) {
                            foreach ($fileUpload as $item => $file) {
                                $k = str_replace('reply_', '', $item);
                                if (!$file->validFileExt($index->img_upload_type)) {
                                    $ret['ret_'.$item] = Language::get('The type of file is invalid');
                                } elseif ($file->getSize() > ($index->img_upload_size * 1024)) {
                                    $ret['ret_'.$item] = Language::get('The file size larger than the limit');
                                } else {
                                    // อัปโหลดได้
                                    $ext = $file->getClientFileExt();
                                    $post[$k] = "$mktime.$ext";
                                    while (is_file(ROOT_PATH.DATA_FOLDER.'board/'.$post[$k])) {
                                        ++$mktime;
                                        $post[$k] = "$mktime.$ext";
                                    }
                                    try {
                                        $file->moveTo(ROOT_PATH.DATA_FOLDER.'board/'.$post[$k]);
                                        // ลบรูปภาพเก่า
                                        if (!empty($index->$k) && $index->$k != $post[$k]) {
                                            @unlink(ROOT_PATH.DATA_FOLDER.'board/'.$index->$k);
                                        }
                                    } catch (\Exception $exc) {
                                        // ไม่สามารถอัปโหลดได้
                                        $ret['ret_'.$item] = Language::get($exc->getMessage());
                                    }
                                }
                            }
                        }
                        if (empty($ret)) {
                            $post['last_update'] = $mktime;
                            if ($id > 0) {
                                // แก้ไข
                                $this->db()->update($this->getTableName('board_r'), $id, $post);
                                // คืนค่า
                                $ret['alert'] = Language::get('Edit comment successfully');
                            } else {
                                // ใหม่
                                $post['ip'] = $request->getClientIp();
                                $post['index_id'] = $index->id;
                                $post['module_id'] = $index->module_id;
                                $id = $this->db()->insert($this->getTableName('board_r'), $post);
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
                                    \Board\Admin\Write\Model::updateCategories((int) $index->module_id);
                                }
                                // คืนค่า
                                $ret['alert'] = Language::get('Thank you for your comment');
                            }
                            // อัปเดตคำถาม
                            $q['comment_date'] = $mktime;
                            $q['last_update'] = $mktime;
                            $this->db()->update($this->getTableName('board_q'), $index->id, $q);
                            // เคลียร์
                            $request->removeToken();
                            // reload
                            $location = WEB_URL.'index.php?module='.$index->module.'&id='.$index_id.'&visited='.$mktime;
                            // ส่งข้อความแจ้งเตือนไปยังไลน์เมื่อมีความคิดเห็นใหม่
                            $line_notifications = !empty($index->line_notifications) && in_array(2, $index->line_notifications);
                            $line_uid = !empty($index->line_uid);
                            if ($line_notifications || $line_uid) {
                                $msg = Language::get('BOARD_NOTIFICATIONS');
                                $msg = implode("\n", array(
                                    (isset($post['sender']) ? $post['sender'].' ' : '').$msg[2].':',
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
                ->from('board_r R')
                ->join('board_q Q', 'INNER', array('Q.id', 'R.index_id'))
                ->join('modules M', 'INNER', array('M.id', 'Q.module_id'))
                ->join('category C', 'LEFT', array(array('C.module_id', 'Q.module_id'), array('C.category_id', 'Q.category_id')))
                ->join('user U', 'LEFT', array('U.id', 'Q.member_id'))
                ->where(array(array('R.id', $id), array('R.index_id', $index_id), array('R.module_id', $module_id)))
                ->toArray()
                ->cacheOn()
                ->first('R.member_id', 'Q.member_id writer_id', 'Q.id', 'Q.comments', 'Q.module_id', 'R.picture', 'Q.locked', 'M.module', 'M.config mconfig', 'C.config', 'C.category_id', 'U.line_uid');
        } else {
            // ใหม่
            $index = $this->db()->createQuery()
                ->from('board_q Q')
                ->join('modules M', 'INNER', array('M.id', 'Q.module_id'))
                ->join('category C', 'LEFT', array(array('C.module_id', 'Q.module_id'), array('C.category_id', 'Q.category_id')))
                ->join('user U', 'LEFT', array('U.id', 'Q.member_id'))
                ->where(array(array('Q.id', $index_id), array('Q.module_id', $module_id)))
                ->toArray()
                ->cacheOn()
                ->first('0 member_id', 'Q.member_id writer_id', 'Q.id', 'Q.comments', 'Q.module_id', 'Q.locked', 'M.module', 'M.config mconfig', 'C.config', 'C.category_id', 'U.line_uid');
        }
        if ($index) {
            // config จากโมดูล
            $index = ArrayTool::unserialize($index['mconfig'], $index);
            // config จากหมวด แทนที่ config จากโมดูล
            if (!empty($index['category_id'])) {
                $index = ArrayTool::unserialize($index['config'], $index);
            }
            unset($index['mconfig']);
            unset($index['config']);
            return (object) $index;
        }
        return false;
    }
}
