<?php
/**
 * @filesource modules/friends/models/action.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Friends\Action;

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
        if ($request->initSession() && $request->isReferer() && preg_match('/(delete|deleting|pin)-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)$/', $request->post('id')->toString(), $match)) {
            $action = $match[1];
            $qid = (int) $match[2];
            $rid = (int) $match[3];
            $no = (int) $match[4];
            $module_id = (int) $match[5];
            // คำถาม
            $index = $this->db()->createQuery()
                ->from('friends Q')
                ->join('modules M', 'INNER', array('M.id', 'Q.module_id'))
                ->where(array(array('Q.id', $qid), array('Q.module_id', $module_id)))
                ->toArray()
                ->first('Q.topic', 'Q.member_id', 'Q.module_id', 'M.module', 'M.config', 'Q.pin');
            $ret = array();
            if ($index === false) {
                // ไม่พบ
                $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
            } else {
                // config
                $index = ArrayTool::unserialize($index['config'], $index);
                unset($index['config']);
                // login
                $login = $request->session('login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''))->all();
                // ผู้ดูแล (ลบ ปักหมุด โพสต์)
                $moderator = Gcms::canConfig($login, $index, 'moderator');
                if ($action === 'pin' && $moderator) {
                    $ret['value'] = $index['pin'] == 0 ? 1 : 0;
                    $this->db()->update($this->getTableName('friends'), $qid, array('pin' => $ret['value']));
                    $ret['title'] = Language::get($ret['value'] == 1 ? 'Unpin' : 'Pin');
                } elseif ($action === 'delete' && $moderator) {
                    // สามารถลบได้
                    $ret['confirm'] = Language::replace('You want to :action :name', array(':action' => Language::get('Delete'), ':name' => Language::get('message')));
                    $action = 'deleting';
                } elseif (in_array($action, array('deleting', 'mdelete')) && $moderator) {
                    // ลบ
                    $this->db()->delete($this->getTableName('friends'), $qid);
                    // ลบรายการออก
                    $ret['remove'] = "L_$qid";
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
