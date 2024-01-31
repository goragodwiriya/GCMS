<?php
/**
 * @filesource modules/index/models/other.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Other;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=other
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * บันทึกการตั้งค่าเว็บไซต์ (system.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, member, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // โหลด config
                    $config = Config::load(CONFIG);
                    $config->member_reserv = array();
                    foreach (explode("\n", $request->post('member_reserv')->text()) as $item) {
                        $config->member_reserv[] = trim($item);
                    }
                    $config->wordrude = array();
                    foreach (explode("\n", $request->post('wordrude')->text()) as $item) {
                        $item = trim($item);
                        if ($item != '') {
                            $config->wordrude[] = $item;
                        }
                    }
                    $config->wordrude_replace = $request->post('wordrude_replace', 'xxx')->toString();
                    $config->counter_digit = max(4, $request->post('counter_digit')->toInt());
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
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
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
