<?php
/**
 * @filesource Widgets/Facebook/Models/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Facebook\Models;

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
     * ค่าติดตั้งเรื่มต้น
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return array(
            'height' => 214,
            'user' => 'gcmscms',
            'show_facepile' => 1,
            'small_header' => 0,
            'hide_cover' => 0
        );
    }

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
                // ค่าที่ส่งมา
                $save = array(
                    'user' => $request->post('user')->username(),
                    'height' => max(70, $request->post('height')->toInt()),
                    'show_facepile' => $request->post('show_facepile')->toBoolean(),
                    'small_header' => $request->post('small_header')->toBoolean(),
                    'hide_cover' => $request->post('hide_cover')->toBoolean()
                );
                // โหลด config
                $config = Config::load(CONFIG);
                $config->facebook_page = $save;
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
