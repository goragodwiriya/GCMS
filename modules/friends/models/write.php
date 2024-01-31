<?php
/**
 * @filesource modules/friends/models/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Friends\Write;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 *  Model สำหรับบันทึกโพสต์
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บันทึกกระทู้
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            // ค่าที่ส่งมา
            $post = array(
                'topic' => $request->post('friends_topic')->topic(),
                'province_id' => $request->post('friends_province')->toInt()
            );
            // อ่านข้อมูลโมดูล
            $index = \Friends\Admin\Index\Model::module($request->post('module_id')->toInt());
            // ตรวจสอบค่าที่ส่งมา
            $ret = array();
            if (!$index || empty($login['id']) || !in_array($login['status'], $index->can_post)) {
                // ไม่พบรายการที่ต้องการ หรือไม่สามารถโพสต์ได้
                $ret['alert'] = Language::get('Can not be performed this request. Because they do not find the information you need or you are not allowed');
            } elseif ($post['topic'] == '') {
                // ไม่ได้กรอกรายละเอียด
                $ret['ret_friends_topic'] = Language::get('Please fill in');
            } else {
                // ตรวจสอบการโพสต์ภายใน 1 วัน
                $search = $this->db()->createQuery()
                    ->from('friends')
                    ->where(array(
                        array('member_id', $login['id']),
                        array('module_id', $index->module_id),
                        array('create_date', '>', strtotime(date('Y-m-d 00:00:00')))
                    ))
                    ->selectCount()
                    ->toArray()
                    ->execute();
                if ($search[0]['count'] >= $index->per_day) {
                    $ret['alert'] = Language::get('Unable to post, you have exceeded posting limit per day');
                }
            }
            if (empty($ret)) {
                // บันทึก
                $post['ip'] = $request->getClientIp();
                $post['create_date'] = time();
                $post['module_id'] = $index->module_id;
                $post['member_id'] = $login['id'];
                $post['pin'] = 0;
                $id = $this->db()->insert($this->getTableName('friends'), $post);
                // คืนค่า
                $ret['alert'] = Language::get('Thank you for your post');
                $ret['location'] = 'reload';
                // เคลียร์
                $request->removeToken();
            }
        } else {
            // สมาชิกเท่านั้น
            $ret['alert'] = Language::get('Members Only');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
