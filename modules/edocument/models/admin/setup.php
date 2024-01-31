<?php
/**
 * @filesource modules/edocument/models/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Admin\Setup;

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
    protected $table = 'edocument A';

    /**
     * query หน้าเพจ เรียงลำดับตาม module,language
     *
     * @return array
     */
    public function getConfig()
    {
        return array(
            'select' => array(
                'A.id',
                'A.document_no',
                'A.topic',
                'A.ext',
                'A.detail',
                array($this->db()->createQuery()->select('email')->from('user U')->where(array('U.id', 'A.sender_id')), 'sender'),
                'A.size',
                'A.last_update',
                'A.downloads',
                'A.file',
                'A.module_id',
                'A.sender_id'
            )
        );
    }

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
                $index = \Index\Adminmodule\Model::getModuleWithConfig('edocument', $request->post('mid')->toInt());
                if ($index && Gcms::canConfig($login, $index, 'can_upload') && preg_match('/^[0-9,]+$/', $id)) {
                    $module_id = (int) $index->module_id;
                    // Model
                    $model = new \Kotchasan\Model();
                    if ($action === 'delete') {
                        // ลบ
                        $id = explode(',', $id);
                        $query = $model->db()->createQuery()->select('file')->from('edocument')->where(array(array('id', $id), array('module_id', $module_id)))->toArray();
                        foreach ($query->execute() as $item) {
                            // ลบไฟล์
                            @unlink(ROOT_PATH.$item['file']);
                        }
                        // ลบข้อมูล
                        $model->db()->createQuery()->delete('edocument', array(array('id', $id), array('module_id', $module_id)))->execute();
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
