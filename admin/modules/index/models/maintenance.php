<?php
/**
 * @filesource modules/index/models/maintenance.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Maintenance;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=maintenance
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * รับค่าจากฟอร์ม (maintenance.php)
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
                    // รับค่าจากการ POST
                    $save = array(
                        'maintenance_mode' => $request->post('maintenance_mode')->toBoolean(),
                        'language' => $request->post('language')->toString(),
                        'detail' => $request->post('detail')->detail()
                    );
                    if (!empty($save['language']) && preg_match('/^[a-z]{2,2}$/', $save['language'])) {
                        // save
                        $template = ROOT_PATH.DATA_FOLDER.'maintenance.'.$save['language'].'.php';
                        $f = @fopen($template, 'wb');
                        if ($f) {
                            fwrite($f, "<?php exit;?>\n".$save['detail']);
                            fclose($f);
                            // โหลด config
                            $config = Config::load(CONFIG);
                            $config->maintenance_mode = $save['maintenance_mode'];
                            // save config
                            if (Config::save($config, CONFIG)) {
                                $ret['alert'] = Language::get('Saved successfully');
                                $ret['location'] = 'reload';
                                // เคลียร์
                                $request->removeToken();
                            } else {
                                $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
                            }
                        } else {
                            $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', DATA_FOLDER.'maintenance.'.$save['language'].'.php');
                        }
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
