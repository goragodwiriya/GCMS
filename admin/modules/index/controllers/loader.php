<?php
/**
 * @filesource modules/index/controllers/loader.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Loader;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * Controller สำหรับโหลดหน้าเว็บด้วย GLoader
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * มาจากการเรียกด้วย GLoader
     * ให้ผลลัพท์เป็น JSON String
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // session, referer
        if ($request->initSession() && $request->isReferer()) {
            // ตัวแปรป้องกันการเรียกหน้าเพจโดยตรง
            define('MAIN_INIT', 'indexhtml');
            // ตรวจสอบการ login
            Login::create($request);
            // template ที่กำลังใช้งานอยู่
            Template::init('skin/admin');
            // View
            Gcms::$view = new \Gcms\Adminview();
            if ($login = Login::adminAccess()) {
                // โหลดโมดูลที่ติดตั้งแล้ว
                Gcms::$module = \Index\Module\Controller::init();
                // เรียก init ของโมดูล
                foreach (Gcms::$module->getInstalledOwners() as $owner => $modules) {
                    $class = ucfirst($owner).'\Admin\Init\Controller';
                    if (class_exists($class) && method_exists($class, 'init')) {
                        $class::init($modules, $login);
                    }
                }
                // โมดูลจาก URL ถ้าไม่มีใช้ default (home)
                $className = \Index\Main\Controller::parseModule($request, 'home');
            } else {
                // ถ้าไม่พบหน้าที่เรียก แสดงหน้า 404
                include APP_PATH.'modules/index/controllers/error.php';
                $className = 'Index\Error\Controller';
            }
            // create Controller
            $controller = new $className();
            // เนื้อหา
            Gcms::$view->setContents(array(
                '/{CONTENT}/' => $controller->render($request)
            ));
            // output เป็น HTML
            $ret = array(
                'detail' => Gcms::$view->renderHTML(Template::load('', '', 'loader')),
                'menu' => $controller->menu(),
                'topic' => $controller->title(),
                'to' => $request->post('to', 'scroll-to')->filter('a-z0-9\-_')
            );
            // คืนค่า JSON
            echo json_encode($ret);
        }
    }
}
