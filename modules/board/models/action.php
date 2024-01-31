<?php
/**
 * @filesource modules/board/models/action.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Action;

use Gcms\Gcms;
use Kotchasan\ArrayTool;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * อ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านบทความที่ $id หรือ $alias
     *
     * @param int    $module_id
     * @param int    $id
     * @param string $alias
     *
     * @return object
     */
    public function view(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && preg_match('/(quote|edit|delete|deleting|pin|lock|print|pdf)-([0-9]+)-([0-9]+)-([0-9]+)-(.*)$/', $request->post('id')->toString(), $match)) {
            $action = $match[1];
            $qid = (int) $match[2];
            $rid = (int) $match[3];
            $no = (int) $match[4];
            $module = $match[5];
            $sql = Sql::create('(CASE WHEN ISNULL(G.`category_id`) THEN M.`config` ELSE G.`config` END) AS `config`');
            if ($rid > 0) {
                // คำตอบ
                $index = $this->db()->createQuery()
                    ->from('board_r C')
                    ->join('board_q Q', 'INNER', array(array('Q.id', 'C.index_id'), array('Q.module_id', 'C.module_id')))
                    ->join('modules M', 'INNER', array('M.id', 'C.module_id'))
                    ->join('category G', 'LEFT', array(array('G.module_id', 'Q.module_id'), array('G.category_id', 'Q.category_id')))
                    ->join('user U', 'LEFT', array('U.id', 'C.member_id'))
                    ->where(array('C.id', $rid))
                    ->toArray()
                    ->first('C.detail', 'Q.category_id', 'C.member_id', 'U.status', 'M.id module_id', 'M.module', $sql, 'C.picture');
            } else {
                // คำถาม
                $index = $this->db()->createQuery()
                    ->from('board_q Q')
                    ->join('modules M', 'INNER', array('M.id', 'Q.module_id'))
                    ->join('category G', 'LEFT', array(array('G.module_id', 'Q.module_id'), array('G.category_id', 'Q.category_id')))
                    ->join('user U', 'LEFT', array('U.id', 'Q.member_id'))
                    ->where(array('Q.id', $qid))
                    ->toArray()
                    ->first('Q.topic', 'Q.detail', 'Q.category_id', 'Q.member_id', 'U.status', 'M.id module_id', 'M.module', $sql, 'Q.picture', 'Q.pin', 'Q.locked');
            }
            $ret = array();
            if ($index === false) {
                // ไม่พบ
                $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
            } else {
                // config
                $index = (object) ArrayTool::unserialize($index['config'], $index);
                unset($index->config);
                // login
                $login = $request->session('login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''))->all();
                // สมาชิก true
                $isMember = $login['status'] > -1;
                // ผู้ดูแล,เจ้าของเรื่อง (ลบ-แก้ไข บทความ,ความคิดเห็นได้)
                $moderator = Gcms::canConfig($login, $index, 'moderator');
                if ($action === 'quote') {
                    // อ้างอิง
                    if (empty($index->detail)) {
                        $ret['detail'] = '';
                    } else {
                        $ret['detail'] = '[quote'.($rid > 0 ? " r=$no]" : ']').Gcms::quote($index->detail, true).'[/quote]';
                    }
                } elseif ($qid > 0 && in_array($action, array('pin', 'lock')) && $moderator) {
                    if ($action == 'pin') {
                        $ret['value'] = $index->pin == 0 ? 1 : 0;
                        $this->db()->update($this->getTableName('board_q'), $qid, array('pin' => $ret['value']));
                        $ret['title'] = Language::get('click to').' '.Language::get($ret['value'] == 1 ? 'Unpin' : 'Pin');
                    } elseif ($action == 'lock') {
                        $ret['value'] = $index->locked == 0 ? 1 : 0;
                        $this->db()->update($this->getTableName('board_q'), $qid, array('locked' => $ret['value']));
                        $ret['title'] = Language::get('click to').' '.Language::get($ret['value'] == 1 ? 'Unlock' : 'Lock');
                    }
                } elseif ($action === 'delete' && $isMember) {
                    // สามารถลบได้ (mod=ลบ,สมาชิก=แจ้งลบ)
                    if ($moderator || $index->member_id == $login['id']) {
                        // ลบ
                        if ($rid > 0) {
                            $ret['confirm'] = Language::replace('You want to :action :name', array(':action' => Language::get('Delete'), ':name' => Language::get('comments'))).' ?';
                        } else {
                            $ret['confirm'] = Language::get('You want to delete this item and all comments').' ?';
                        }
                        $action = 'deleting';
                    }
                } elseif (in_array($action, array('deleting', 'mdelete')) && $moderator) {
                    // ลบ mod หรือ เจ้าของ
                    if ($rid > 0) {
                        // ลบรูปภาพในคำตอบ
                        @unlink(ROOT_PATH.DATA_FOLDER.'board/'.$index->picture);
                        // ลบความคิดเห็น
                        $this->db()->delete($this->getTableName('board_r'), $rid);
                        // อ่านคำตอบล่าสุดของคำถามนี้
                        $sql = Sql::create("(CASE WHEN ISNULL(U.`id`) THEN (CASE WHEN C.`sender`='' THEN C.`email` ELSE C.`sender` END) WHEN U.`displayname`='' THEN U.`email` ELSE U.`displayname` END) AS `name`");
                        $r = $this->db()->createQuery()
                            ->from('board_r C')
                            ->join('user U', 'LEFT', array('U.id', 'C.member_id'))
                            ->where(array(array('C.index_id', $qid), array('C.module_id', $index->module_id)))
                            ->order('C.id DESC')
                            ->toArray()
                            ->first('C.id', 'C.module_id', 'C.last_update', 'U.id member_id', 'U.status', $sql);
                        // อัปเดตคำถาม
                        $this->db()->createQuery()
                            ->update('board_q')
                            ->set(array(
                                'comment_id' => $r ? (int) $r['id'] : 0,
                                'commentator' => $r ? $r['name'] : '',
                                'commentator_id' => $r ? (int) $r['member_id'] : 0,
                                'comment_date' => $r ? (int) $r['last_update'] : 0,
                                'comments' => $this->db()->createQuery()->selectCount()->from('board_r')->where(array(array('index_id', $qid), array('module_id', $index->module_id))),
                                'hassubpic' => $this->db()->createQuery()->selectCount()->from('board_r')->where(array(array('index_id', $qid), array('module_id', $index->module_id), array('picture', '!=', ''))),
                                'last_update' => time()
                            ))
                            ->where($qid)
                            ->execute();
                        // คืนค่า ID ที่ลบ
                        $ret['remove'] = "R_$rid";
                    } else {
                        // ลบรูปภาพทั้งหมดภายในคำตอบของคำถามนี้
                        $query = $this->db()->createQuery()
                            ->select('picture')
                            ->from('board_r')
                            ->where(array(
                                array('index_id', $qid),
                                array('module_id', $index->module_id),
                                array('picture', '!=', "''")
                            ))
                            ->toArray();
                        foreach ($query->execute() as $item) {
                            @unlink(ROOT_PATH.DATA_FOLDER.'board/'.$index->picture);
                        }
                        // ลบรูปภาพของคำถาม
                        @unlink(ROOT_PATH.DATA_FOLDER.'board/'.$index->picture);
                        @unlink(ROOT_PATH.DATA_FOLDER.'board/thumb-'.$index->picture);
                        // ลบ
                        $this->db()->delete($this->getTableName('board_q'), $qid);
                        $this->db()->delete($this->getTableName('board_r'), array(array('index_id', $qid), array('module_id', $index->module_id)));
                        if ($action == 'deleting') {
                            // กลับไปหน้าหลักของโมดูลที่เลือก
                            $ret['location'] = WEB_URL."index.php?module=$module";
                        } else {
                            // ลบรายการออก
                            $ret['remove'] = "L_$qid";
                        }
                    }
                    // อัปเดตหมวดหมู่
                    if ($index->category_id > 0) {
                        // อัปเดตจำนวนเรื่อง และ ความคิดเห็น ในหมวด
                        \Board\Admin\Write\Model::updateCategories((int) $index->module_id);
                    }
                } elseif ($action == 'edit' && ($moderator || ($isMember && $index->member_id == $login['id']))) {
                    // แก้ไข mod หรือ เจ้าของ
                    if ($rid > 0) {
                        $ret['location'] = WEB_URL."index.php?module=$module-edit&rid=$rid";
                    } else {
                        $ret['location'] = WEB_URL."index.php?module=$module-edit&qid=$qid";
                    }
                }
                $ret['action'] = $action;
                $ret['qid'] = $qid;
            }
            // คืนค่า json
            if (!empty($ret)) {
                echo json_encode($ret);
            }
        }
    }
}
