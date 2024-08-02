<?php
/**
 * @filesource modules/board/controllers/edit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Edit;

use Kotchasan\Http\Request;

/**
 * แก้ไขกระทู้และความคิดเห็น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * แก้ไขกระทู้และความคิดเห็น
     *
     * @param Request $request
     * @param object  $module  ข้อมูลโมดูลจาก database
     *
     * @return object
     */
    public function init(Request $request, $module)
    {
        // รายการที่แก้ไข
        $qid = $request->request('qid')->toInt();
        $rid = $request->request('rid')->toInt();
        // ตรวจสอบโมดูลและอ่านข้อมูลโมดูล
        if ($qid > 0) {
            $index = \Board\Module\Model::getQuestionById($qid, $module);
            if ($index) {
                // ฟอร์มแก้ไขกระทู้
                return createClass('Board\Writeedit\View')->index($request, $index);
            }
        } elseif ($rid > 0) {
            $index = \Board\Module\Model::getCommentById($rid, $module);
            if ($index) {
                // ฟอร์มแก้ไขความคิดเห็น
                return createClass('Board\Replyedit\View')->index($request, $index);
            }
        }
        // 404
        return createClass('Index\Error\Controller')->init('board');
    }
}
