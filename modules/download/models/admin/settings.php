<?php
/**
 * @filesource modules/download/models/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Admin\Settings;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 *  บันทึกการตั้งค่า
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค่าติดตั้งเริ่มต้น
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return array(
            'file_typies' => array('doc', 'ppt', 'pptx', 'docx', 'rar', 'zip', 'jpg', 'pdf'),
            'upload_size' => 2097152,
            'list_per_page' => 20,
            'sort' => 1,
            'can_download' => array(-1, 0, 1),
            'moderator' => array(1),
            'can_upload' => array(1),
            'can_config' => array(1)
        );
    }

    /**
     * บันทึกข้อมูล config ของโมดูล
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // referer, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $typies = array();
                foreach (explode(',', strtolower($request->post('file_typies')->filter('a-zA-Z0-9,'))) as $typ) {
                    if ($typ != '') {
                        $typies[$typ] = $typ;
                    }
                }
                $save = array(
                    'file_typies' => array_keys($typies),
                    'upload_size' => $request->post('upload_size')->toInt(),
                    'list_per_page' => $request->post('list_per_page')->toInt(),
                    'sort' => $request->post('sort')->toInt(),
                    'can_download' => $request->post('can_download', array())->toInt(),
                    'can_upload' => $request->post('can_upload', array())->toInt(),
                    'moderator' => $request->post('moderator', array())->toInt(),
                    'can_config' => $request->post('can_config', array())->toInt()
                );
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('download', $request->post('id')->toInt());
                // สามารถตั้งค่าได้
                if ($index && Gcms::canConfig($login, $index, 'can_config')) {
                    if (empty($save['file_typies'])) {
                        // คืนค่า input ที่ error
                        $ret['ret_file_typies'] = 'this';
                    } else {
                        // บันทึก
                        $save['can_upload'][] = 1;
                        $save['moderator'][] = 1;
                        $save['can_config'][] = 1;
                        $this->db()->update($this->getTableName('modules'), $index->module_id, array('config' => serialize($save)));
                        // คืนค่า
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'reload';
                        // เคลียร์
                        $request->removeToken();
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
