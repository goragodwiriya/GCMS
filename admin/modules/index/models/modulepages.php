<?php
/**
 * @filesource modules/index/models/modulepages.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Modulepages;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * รายการ topic, descript ของหน้าเว็บไซต์ย่อยของโมดูล (modulepages.php)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลสำหรับใส่ลงในตาราง
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('I.id', 'M.id AS module_id', 'M.module', 'I.page', 'D.topic', 'D.description', 'I.language')
            ->from('modules M')
            ->join('index_detail D', 'INNER', array('D.module_id', 'M.id'))
            ->join('index I', 'INNER', array(array('I.id', 'D.id'), array('I.module_id', 'M.id'), array('I.language', 'D.language')))
            ->where(array('I.index', 2));
    }

    /**
     * รับค่าจาก action ของ table
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = array();
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_config')) {
                // ค่าที่ส่งมา
                $action = $request->post('action')->toString();
                $id = $request->post('id')->toInt();
                if ($action === 'delete') {
                    // ลบ
                    $this->db()->delete($this->getTableName('index'), $id);
                    $this->db()->delete($this->getTableName('index_detail'), $id);
                    // คืนค่า
                    $ret['delete_id'] = $request->post('src')->toString().'_'.$id;
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
