<?php
/**
 * @filesource Widgets/Rss/Models/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Rss\Models;

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
     * รับค่าจากฟอร์ม (Settings.php)
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
                    'url' => $request->post('rss_url')->url(),
                    'topic' => $request->post('rss_topic')->topic(),
                    'index' => $request->post('rss_index')->number(),
                    'rows' => max(1, $request->post('rss_rows')->toInt()),
                    'cols' => max(1, $request->post('rss_cols')->toInt())
                );
                $id = $request->post('rss_id')->toInt();
                // โหลด config
                $config = Config::load(CONFIG);
                if ($id > 0 && !isset($config->rss_tabs[$id])) {
                    $ret['alert'] = Language::get('Unable to complete the transaction');
                } elseif ($save['url'] == '') {
                    $ret['ret_rss_url'] = 'Please fill in';
                } elseif ($save['topic'] == '') {
                    $ret['ret_rss_topic'] = 'Please fill in';
                } else {
                    if (!isset($config->rss_tabs)) {
                        $config->rss_tabs = array();
                    }
                    $n = 1;
                    $cfg = array();
                    foreach ($config->rss_tabs as $i => $v) {
                        if ($i == $id) {
                            $cfg[$n] = $save;
                        } else {
                            $cfg[$n] = $v;
                        }
                        ++$n;
                    }
                    if ($id == 0) {
                        $cfg[$n] = $save;
                    }
                    $config->rss_tabs = $cfg;
                    // save config
                    if (Config::save($config, CONFIG)) {
                        $ret['alert'] = Language::get('Saved successfully');
                        $ret['location'] = 'index.php?module=Rss-settings';
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
