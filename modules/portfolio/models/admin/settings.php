<?php
/**
 * @filesource modules/portfolio/models/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Portfolio\Admin\Settings;

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
            'width' => 400,
            'height' => 400,
            'rows' => 4,
            'cols' => 2,
            'can_write' => array(1),
            'can_config' => array(1)
        );
    }

    /**
     * เมธอดสำหรับการติดตั้งโมดูลแบบใช้ซ้ำได้
     *
     * @param array $module ข้อมูลโมดูล
     */
    public static function install($module)
    {
        // อัปเดตชื่อตาราง
        \Index\Install\Model::updateTables(array('portfolio' => 'portfolio'));
        // อัปเดต database
        \Index\Install\Model::execute(ROOT_PATH.'modules/portfolio/models/admin/sql.php');
        // สร้างไดเร็คทอรี่เก็บข้อมูลโมดูล
        \Kotchasan\File::makeDirectory(ROOT_PATH.DATA_FOLDER.'portfolio/');
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
                $save = array(
                    'width' => max(100, $request->post('width')->toInt()),
                    'height' => max(100, $request->post('height')->toInt()),
                    'rows' => $request->post('rows')->toInt(),
                    'cols' => $request->post('cols')->toInt(),
                    'can_write' => $request->post('can_write', array())->toInt(),
                    'can_config' => $request->post('can_config', array())->toInt()
                );
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('portfolio', $request->post('id')->toInt());
                // สามารถตั้งค่าได้
                if ($index && Gcms::canConfig($login, $index, 'can_config')) {
                    // บันทึก
                    $save['can_write'][] = 1;
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
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
