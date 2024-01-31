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
 * Controller สำหรับโหลดข้อมูลด้วย GLoader
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * มาจากการเรียกด้วย GLoader
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
            if (!empty($_SESSION['skin']) && is_file(APP_PATH.'skin/'.$_SESSION['skin'].'/style.css')) {
                self::$cfg->skin = $_SESSION['skin'];
            }
            Template::init('skin/'.self::$cfg->skin);
            // counter และ useronline
            $counter = \Index\Counter\Model::init($request);
            // View
            Gcms::$view = new \Index\Loader\View();
            // โหลดเมนูทั้งหมดเรียงตามลำดับเมนู (รายการแรกคือหน้า Home)
            Gcms::$menu = \Index\Menu\Controller::create();
            // โหลดโมดูลที่ติดตั้งแล้ว และสามารถใช้งานได้
            Gcms::$module = \Index\Module\Controller::init(Gcms::$menu, $counter->new_day);
            // หน้า home มาจากเมนูรายการแรก
            $home = Gcms::$menu->homeMenu();
            if ($home) {
                $home->canonical = WEB_URL.'index.php';
                // breadcrumb หน้า home
                Gcms::$view->addBreadcrumb($home->canonical, $home->menu_text, $home->menu_tooltip, 'icon-home');
            }
            // ตรวจสอบโมดูลที่เรียก
            $posts = $request->getParsedBody();
            $modules = Gcms::$module->checkModuleCalled($posts);
            if (!empty($modules)) {
                // โหลดโมดูลที่เรียก
                $page = createClass($modules->className)->{$modules->method}($request->withQueryParams($posts), $modules->module);
            }
            if (empty($page)) {
                // ไม่พบหน้าที่เรียก (index)
                $page = createClass('Index\Error\Controller')->init('index');
            }
            // output เป็น HTML
            $ret = array(
                'db_elapsed' => round(microtime(true) - REQUEST_TIME, 4),
                'db_quries' => \Kotchasan\Database\Driver::queryCount(),
                'counter' => $counter->counter,
                'counter_today' => $counter->counter_today,
                'pages_view' => $counter->pages_view,
                'useronline' => $counter->useronline
            );
            foreach ($page as $key => $value) {
                $ret[$key] = $value;
            }
            if (empty($ret['menu'])) {
                $ret['menu'] = $ret['module'];
            }
            $ret['detail'] = Gcms::$view->renderHTML($page->detail);
            // คืนค่า JSON
            echo json_encode($ret);
        }
    }
}
