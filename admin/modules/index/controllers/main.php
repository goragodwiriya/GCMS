<?php
/**
 * @filesource modules/index/controllers/main.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Main;

use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * Controller หลัก สำหรับแสดงหน้าเว็บไซต์
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟังก์ชั่นแปลงชื่อโมดูลที่ส่งมาเป็น Controller Class และโหลดคลาสไว้ เช่น
     * home = Index\Home\Controller
     * person-index = Person\Index\Controller
     *
     * @param Request $request
     * @param string  $default ถ้าไม่ระบุจะคืนค่า Error Controller
     *
     * @return string|null คืนค่าชื่อคลาส ถ้าไม่พบจะคืนค่า null
     */
    public static function parseModule($request, $default = null)
    {
        if (preg_match('/^([a-z]+)([\/\-]([a-z]+))?$/i', $request->request('module', '')->toString(), $match)) {
            if (empty($match[3])) {
                if (is_file(APP_PATH.'modules/'.$match[1].'/controllers/index.php')) {
                    $owner = $match[1];
                    $module = empty($default) ? 'error' : $default;
                } else {
                    $owner = 'index';
                    $module = $match[1];
                }
            } else {
                $owner = $match[1];
                $module = $match[3];
            }
        } else {
            // ถ้าไม่ระบุ module มาแสดงหน้า $default
            $owner = 'index';
            $module = empty($default) ? 'error' : $default;
        }
        // ตรวจสอบหน้าที่เรียก
        if (is_file(APP_PATH.'modules/'.$owner.'/controllers/'.$module.'.php')) {
            // โหลดคลาส ถ้าพบโมดูลที่เรียก
            include APP_PATH.'modules/'.$owner.'/controllers/'.$module.'.php';
            return ucfirst($owner).'\\'.ucfirst($module).'\Controller';
        } elseif (is_file(ROOT_PATH.'modules/'.$owner.'/controllers/admin/'.$module.'.php')) {
            // เรียกโมดูลที่ติดตั้ง
            include ROOT_PATH.'modules/'.$owner.'/controllers/admin/'.$module.'.php';
            return ucfirst($owner).'\Admin\\'.ucfirst($module).'\Controller';
        } elseif (is_file(ROOT_PATH.'Widgets/'.ucfirst($owner).'/Controllers/'.ucfirst($module).'.php')) {
            // เรียก Widgets ที่ติดตั้ง
            include ROOT_PATH.'Widgets/'.ucfirst($owner).'/Controllers/'.ucfirst($module).'.php';
            return 'Widgets\\'.ucfirst($owner).'\\Controllers\\'.ucfirst($module);
        } else {
            // หน้า default ถ้าไม่พบหน้าที่เรียก
            include APP_PATH.'modules/index/controllers/home.php';
            return 'Index\Home\Controller';
        }
        return null;
    }

    /**
     * หน้าหลักเว็บไซต์
     *
     * @param Request $request
     *
     * @return string
     */
    public function execute(Request $request)
    {
        // โมดูลจาก URL ถ้าไม่มีใช้ default (home)
        $className = self::parseModule($request, 'home');
        // create Class
        $controller = new $className();
        // tempalate
        $template = Template::create('', '', 'main');
        $template->add(array(
            '/{CONTENT}/' => $controller->render($request)
        ));
        // ข้อความ title bar
        $this->title = $controller->title();
        // เมนูที่เลือก
        $this->menu = $controller->menu();
        return $template->render();
    }
}
