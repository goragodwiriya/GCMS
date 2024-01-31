<?php
/**
 * @filesource Widgets/Gallery/Models/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Gallery\Models;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * บันทึกการตั้งค่าเว็บไซต์
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
            'rows' => 2,
            'cols' => 2,
            'url' => 'https://gallery.gcms.in.th/gallery.rss'
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
                $save = array(
                    'url' => $request->post('url')->url(),
                    'rows' => max(1, $request->post('rows')->toInt()),
                    'cols' => max(1, $request->post('cols')->toInt())
                );
                // โหลด config
                $config = Config::load(CONFIG);
                if ($save['url'] == '') {
                    $ret['ret_url'] = 'Please fill in';
                } else {
                    $config->rss_gallery = $save;
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
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
