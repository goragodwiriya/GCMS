<?php
/**
 * @filesource modules/index/models/intro.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Intro;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=intro
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * บันทึกข้อมูล (intro.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = array();
        // session, token, สามารถตั้งค่าระบบได้, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::adminAccess()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = array(
                        'show_intro' => $request->post('show_intro')->toBoolean(),
                        'language' => $request->post('language')->toString(),
                        'detail' => $request->post('detail')->detail()
                    );
                    if (!empty($save['language']) && preg_match('/^[a-z]{2,2}$/', $save['language'])) {
                        // save
                        $template = ROOT_PATH.DATA_FOLDER.'intro.'.$save['language'].'.php';
                        $f = @fopen($template, 'wb');
                        if ($f) {
                            fwrite($f, "<?php exit;?>\n".$save['detail']);
                            fclose($f);
                            // โหลด config
                            $config = Config::load(CONFIG);
                            $config->show_intro = $save['show_intro'];
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
                            $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', DATA_FOLDER.'intro.'.$save['language'].'.php');
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
