<?php
/**
 * @filesource modules/document/models/action.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Action;

use Gcms\Gcms;
use Kotchasan\ArrayTool;
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
            if ($rid > 0) {
                // คำตอบ
                $index = $this->db()->createQuery()
                    ->from('comment C')
                    ->join('index Q', 'INNER', array(array('Q.id', 'C.index_id'), array('Q.module_id', 'C.module_id'), array('Q.index', 0)))
                    ->join('modules M', 'INNER', array('M.id', 'C.module_id'))
                    ->where(array('C.id', $rid))
                    ->toArray()
                    ->first('C.detail', 'Q.category_id', 'C.member_id', 'M.id module_id', 'M.module', 'M.config');
            } else {
                // คำถาม
                $index = $this->db()->createQuery()
                    ->from('index Q')
                    ->join('index_detail D', 'INNER', array(array('D.id', 'Q.id'), array('D.module_id', 'Q.module_id'), array('D.language', array(Language::name(), ''))))
                    ->join('modules M', 'INNER', array('M.id', 'Q.module_id'))
                    ->where(array('Q.id', $qid))
                    ->toArray()
                    ->first('D.topic', 'D.detail', 'Q.category_id', 'Q.member_id', 'M.id module_id', 'M.module', 'M.config');
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
                } elseif ($action === 'delete' && $isMember) {
                    // สามารถลบได้ (mod=ลบ,สมาชิก=แจ้งลบ)
                    if ($moderator || $index->member_id == $login['id']) {
                        // ลบ
                        if ($rid > 0) {
                            $ret['confirm'] = Language::replace('You want to :action :name', array(':action' => Language::get('Delete'), ':name' => Language::get('comments'))).' ?';
                        }
                        $action = 'deleting';
                    }
                } elseif (in_array($action, array('deleting', 'mdelete')) && $moderator) {
                    // ลบ mod หรือ เจ้าของ
                    if ($rid > 0) {
                        // ลบความคิดเห็น
                        $this->db()->delete($this->getTableName('comment'), $rid);
                        // อัปเดตจำนวนคำตอบของคำถาม
                        \Index\Comment\Model::update($qid, (int) $index->module_id);
                        $ret['remove'] = "R_$rid";
                    }
                    // อัปเดตหมวดหมู่
                    if ($index->category_id > 0) {
                        // อัปเดตจำนวนเรื่อง และ ความคิดเห็น ในหมวด
                        \Document\Admin\Write\Model::updateCategories((int) $index->module_id);
                    }
                } elseif ($action == 'edit' && ($moderator || ($isMember && $index->member_id == $login['id']))) {
                    // แก้ไข mod หรือ เจ้าของ
                    if ($rid > 0) {
                        $ret['location'] = WEB_URL."index.php?module=$module-edit&rid=$rid";
                    }
                }
                $ret['action'] = $action;
            }
            // คืนค่า json
            if (!empty($ret)) {
                echo json_encode($ret);
            }
        }
    }
}
