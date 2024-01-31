<?php
/**
 * @filesource modules/video/models/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Video\Admin\Setup;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับแสดงรายการบทความ (setup.php)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Orm\Field
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video';

    /**
     * รับค่าจาก action ของ table (setup.php)
     *
     * @param Request $request
     */
    public static function action(Request $request)
    {
        $ret = array();
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $id = $request->post('id')->toString();
                $action = $request->post('action')->toString();
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('video', $request->post('mid')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_write') && preg_match('/^[0-9,]+$/', $id)) {
                    $module_id = (int) $index->module_id;
                    // Model
                    $model = new \Kotchasan\Model();
                    if ($action === 'delete') {
                        // ลบ
                        $id = explode(',', $id);
                        $query = $model->db()->createQuery()->select('youtube')->from('video')->where(array(array('id', $id), array('module_id', $module_id)))->toArray();
                        foreach ($query->execute() as $item) {
                            // ลบไฟล์
                            @unlink(ROOT_PATH.DATA_FOLDER.'video/'.$item['youtube'].'.jpg');
                        }
                        // ลบข้อมูล
                        $model->db()->createQuery()->delete('video', array(array('id', $id), array('module_id', $module_id)))->execute();
                        // reload
                        $ret['location'] = 'reload';
                    }
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
