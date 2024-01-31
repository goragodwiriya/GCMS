<?php
/**
 * @filesource modules/gallery/models/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Settings;

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
            'icon_width' => 400,
            'icon_height' => 300,
            'image_width' => 800,
            'img_typies' => array('jpg', 'jpeg'),
            'rows' => 3,
            'cols' => 4,
            'sort' => 1,
            'can_view' => array(-1, 0, 1),
            'can_write' => array(1),
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
                $save = array(
                    'icon_width' => max(75, $request->post('icon_width')->toInt()),
                    'icon_height' => max(75, $request->post('icon_height')->toInt()),
                    'image_width' => max(400, $request->post('image_width')->toInt()),
                    'img_typies' => $request->post('img_typies', array())->toString(),
                    'rows' => $request->post('rows')->toInt(),
                    'cols' => $request->post('cols')->toInt(),
                    'sort' => $request->post('sort')->toInt(),
                    'can_view' => $request->post('can_view', array())->toInt(),
                    'can_write' => $request->post('can_write', array())->toInt(),
                    'can_config' => $request->post('can_config', array())->toInt()
                );
                // อ่านข้อมูลโมดูล และ config
                $index = \Index\Adminmodule\Model::getModuleWithConfig('gallery', $request->post('id')->toInt());
                // สามารถตั้งค่าได้
                if ($index && Gcms::canConfig($login, $index, 'can_config')) {
                    if (empty($save['img_typies'])) {
                        // คืนค่า input ที่ error
                        $ret['ret_img_typies'] = Language::get('Please select at least one item');
                    } else {
                        // บันทึก
                        $save['can_view'][] = 1;
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
