<?php
/**
 * @filesource modules/index/models/debug.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Debug;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * get debug datas
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นจัดการ debug อ่าน,ลบ
     */
    public function action(Request $request)
    {
        // session, referer, admin
        if ($request->initSession() && $request->isReferer() && $login = Login::isAdmin()) {
            if (Login::notDemoMode($login)) {
                // action
                $action = $request->post('action')->toString();
                // file debug
                $debug = ROOT_PATH.DATA_FOLDER.'logs/error_log.php';
                if (is_file($debug)) {
                    if ($action == 'get') {
                        // อ่าน debug
                        $t = $request->post('t')->toString();
                        foreach (file($debug) as $i => $row) {
                            if (preg_match('/^\[([0-9\-:\s]+)\][\s]+([A-Z]+):[\s]+(.*)/', trim($row), $match)) {
                                if ($match[1] > $t) {
                                    echo "$match[1]\t$match[2]\t$match[3]\n";
                                }
                            }
                        }
                    } elseif ($action == 'clear') {
                        // ลบไฟล์ debug
                        unlink($debug);
                    }
                }
            }
        }
    }
}
