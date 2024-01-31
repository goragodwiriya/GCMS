<?php
/**
 * @filesource Widgets/Twitter/Models/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Twitter\Models;

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
            'id' => '348368123554062336',
            'user' => 'goragod',
            'height' => 200,
            'amount' => 2,
            'theme' => 'light',
            'border_color' => '',
            'link_color' => ''
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
                    'id' => $request->post('twitter_id')->number(),
                    'user' => $request->post('twitter_user')->username(),
                    'height' => max(100, $request->post('twitter_height')->toInt()),
                    'amount' => $request->post('twitter_amount')->toInt(),
                    'theme' => $request->post('twitter_theme')->topic(),
                    'link_color' => $request->post('twitter_link_color')->color(),
                    'border_color' => $request->post('twitter_border_color')->color()
                );
                // โหลด config
                $config = Config::load(CONFIG);
                $config->twitter = $save;
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
