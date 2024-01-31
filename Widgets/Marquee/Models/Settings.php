<?php
/**
 * @filesource Widgets/Marquee/Models/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Marquee\Models;

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
                // ค่าที่ส่งมา
                $save = array(
                    'speed' => max(1, $request->post('marquee_speed')->toInt()),
                    'style' => $request->post('marquee_style')->username(),
                    'text' => trim(preg_replace('/[\r\n\t\s]+/', ' ', $request->post('marquee_text')->detail()))
                );
                // โหลด config
                $config = Config::load(CONFIG);
                $config->marquee = $save;
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
