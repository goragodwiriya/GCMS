<?php
/**
 * @filesource modules/product/models/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Admin\Settings;

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
            'product_no' => 'P%04d',
            'thumb_width' => 696,
            'image_width' => 800,
            'img_typies' => array('jpg', 'jpeg'),
            'rows' => 3,
            'cols' => 4,
            'sort' => 1,
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
        \Index\Install\Model::updateTables(array('product' => 'product', 'product_detail' => 'product_detail', 'product_price' => 'product_price'));
        // อัปเดต database
        \Index\Install\Model::execute(ROOT_PATH.'modules/product/models/admin/sql.php');
        // สร้างไดเร็คทอรี่เก็บข้อมูลโมดูล
        \Kotchasan\File::makeDirectory(ROOT_PATH.DATA_FOLDER.'product/');
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
                    'product_no' => $request->post('product_no')->topic(),
                    'currency_unit' => $request->post('currency_unit')->filter('A-Z'),
                    'thumb_width' => max(75, $request->post('thumb_width')->toInt()),
                    'image_width' => max(400, $request->post('image_width')->toInt()),
                    'img_typies' => $request->post('img_typies', array())->toString(),
                    'rows' => $request->post('rows')->toInt(),
                    'cols' => $request->post('cols')->toInt(),
                    'sort' => $request->post('sort')->toInt(),
                    'can_write' => $request->post('can_write', array())->toInt(),
                    'can_config' => $request->post('can_config', array())->toInt()
                );
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('product', $request->post('id')->toInt());
                // สามารถตั้งค่าได้
                if ($index && Gcms::canConfig($login, $index, 'can_config')) {
                    if (empty($save['img_typies'])) {
                        // คืนค่า input ที่ error
                        $ret['ret_img_typies'] = Language::get('Please select at least one item');
                    } else {
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
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
