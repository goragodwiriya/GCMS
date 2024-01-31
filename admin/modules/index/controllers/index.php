<?php
/**
 * @filesource modules/index/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Index;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Template;

/**
 * Controller หลัก สำหรับแสดง backend ของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * หน้าหลักเว็บไซต์ (index.html)
     * ให้ผลลัพท์เป็น HTML
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // ตัวแปรป้องกันการเรียกหน้าเพจโดยตรง
        define('MAIN_INIT', 'indexhtml');
        // session cookie
        $request->initSession();
        // ตรวจสอบการ login
        Login::create($request);
        // template ที่กำลังใช้งานอยู่
        Template::init('skin/admin');
        // View
        Gcms::$view = new \Gcms\Adminview();
        if ($login = Login::adminAccess()) {
            // โหลดโมดูลที่ติดตั้งแล้ว
            Gcms::$module = \Index\Module\Controller::init();
            // โหลดเมนู
            Gcms::$menu = \Index\Menu\Controller::init($login);
            // เรียก init ของโมดูล
            foreach (Gcms::$module->getInstalledOwners() as $owner => $modules) {
                $class = ucfirst($owner).'\Admin\Init\Controller';
                if (class_exists($class) && method_exists($class, 'init')) {
                    $class::init($modules, $login);
                }
            }
            // Controller หลัก
            $main = new \Index\Main\Controller();
            $bodyclass = 'mainpage';
            // ckeditor
            Gcms::$view->addJavascript(WEB_URL.'ckeditor/ckeditor.js');
        } else {
            // forgot, login, register
            $main = new \Index\Welcome\Controller();
            $bodyclass = 'loginpage';
        }
        $languages = array();
        $uri = $request->getUri();
        foreach (Gcms::installedLanguage() as $item) {
            $languages[$item] = '<li><a id=lang_'.$item.' href="'.$uri->withParams(array('lang' => $item), true).'" title="{LNG_Language} '.strtoupper($item).'" style="background-image:url('.WEB_URL.'language/'.$item.'.gif)" tabindex=1>&nbsp;</a></li>';
        }
        // เนื้อหา
        Gcms::$view->setContents(array(
            // main template
            '/{MAIN}/' => $main->execute($request),
            // GCMS Version
            '/{VERSION}/' => self::$cfg->version,
            // language menu
            '/{LANGUAGES}/' => implode('', $languages),
            // title
            '/{TITLE}/' => $main->title(),
            // class สำหรับ body
            '/{BODYCLASS}/' => $bodyclass
        ));
        if ($login) {
            Gcms::$view->setContents(array(
                // ID สมาชิก
                '/{LOGINID}/' => $login['id'],
                // แสดงชื่อคน Login
                '/{LOGINNAME}/' => empty($login['name']) ? $login['email'] : $login['name'],
                // สถานะสมาชิก
                '/{STATUS}/' => $login['status'],
                // เมนู
                '/{MENUS}/' => Gcms::$menu->render($main->menu())
            ));
        }
        // ส่งออก เป็น HTML
        $response = new Response();
        $response->withContent(Gcms::$view->renderHTML())->send();
    }
}
