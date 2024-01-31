<?php
/**
 * @filesource modules/index/controllers/cron.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Cron;

use Kotchasan\Http\Request;

/**
 * Controller สำหรับ Cron เรียกใช้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * มาจากการเรียกด้วย Cron
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        if (defined('MAIN_INIT')) {
            // ไดเร็คทอรี่ที่ติดตั้งโมดูล
            $dir = ROOT_PATH.'modules/';
            // โมดูลที่ติดตั้ง
            $f = @opendir($dir);
            if ($f) {
                while (false !== ($owner = readdir($f))) {
                    if ($owner != '.' && $owner != '..' && $owner != 'index' && $owner != 'js' && $owner != 'css') {
                        if (is_file($dir.$owner.'/controllers/cron.php')) {
                            include $dir.$owner.'/controllers/cron.php';
                            $class = ucfirst($owner).'\Cron\Controller';
                            if (method_exists($class, 'init')) {
                                createClass($class)->init();
                            }
                        }
                    }
                }
                closedir($f);
            }
        }
    }
}
