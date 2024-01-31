<?php
/**
 * @filesource Widgets/Map/Controllers/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Map\Models;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * บันทึกการตั้งค่า
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\KBase
{
    /**
     * บันทึกการตั้งค่า (Settings.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                // โหลด config
                $config = Config::load(CONFIG);
                // ค่าที่ส่งมา
                $config->map_api_key = $request->post('map_api_key')->topic();
                $config->map_height = max(100, $request->post('map_height')->toInt());
                $config->map_zoom = max(1, $request->post('map_zoom')->toInt());
                $config->map_latitude = $request->post('map_latitude')->topic();
                $config->map_lantitude = $request->post('map_lantitude')->topic();
                $config->map_info = $request->post('map_info')->textarea();
                $config->map_info_latitude = $request->post('map_info_latitude')->topic();
                $config->map_info_lantitude = $request->post('map_info_lantitude')->topic();
                // save config
                if (Config::save($config, CONFIG)) {
                    $ret['alert'] = Language::get('Saved successfully');
                    $ret['location'] = 'reload';
                    // เคลียร์
                    $request->removeToken();
                } else {
                    // ไม่สามารถบันทึก config ได้
                    $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
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
