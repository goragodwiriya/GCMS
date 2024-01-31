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
 * Controller สำหรับแสดงหน้าเว็บ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
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
        self::$cfg->skin = $request->get('skin', $request->session('skin', self::$cfg->skin)->toString())->filter('a-z0-9\-');
        self::$cfg->skin = is_file(APP_PATH.'skin/'.self::$cfg->skin.'/style.css') ? self::$cfg->skin : 'rooster';
        $_SESSION['skin'] = self::$cfg->skin;
        Template::init('skin/'.self::$cfg->skin);
        // ตรวจสอบหน้าที่จะแสดง
        if (!empty(self::$cfg->maintenance_mode) && !Login::isAdmin()) {
            Gcms::$view = new \Index\Maintenance\View();
        } elseif (!empty(self::$cfg->show_intro) && str_replace(array(BASE_PATH, '/'), '', $request->getUri()->getPath()) == '') {
            Gcms::$view = new \Index\Intro\View();
        } else {
            // View
            Gcms::$view = new \Gcms\View();
            // โหลดเมนูทั้งหมดเรียงตามลำดับเมนู (รายการแรกคือหน้า Home)
            Gcms::$menu = \Index\Menu\Controller::create();
            // counter
            $counter = \Index\Counter\Model::init($request);
            // โหลดโมดูลที่ติดตั้งแล้ว และสามารถใช้งานได้ และ โหลด Counter
            Gcms::$module = \Index\Module\Controller::init(Gcms::$menu, $counter->new_day);
            // ข้อมูลเว็บไซต์
            Gcms::$site = array(
                '@type' => 'Organization',
                'name' => self::$cfg->web_title,
                'description' => self::$cfg->web_description,
                'url' => WEB_URL.'index.php'
            );
            // logo
            $img_logo = '{WEBTITLE}';
            $logo = '';
            if (!empty(self::$cfg->logo) && is_file(ROOT_PATH.DATA_FOLDER.'image/'.self::$cfg->logo)) {
                $info = @getimagesize(ROOT_PATH.DATA_FOLDER.'image/'.self::$cfg->logo);
                if ($info && $info[0] > 0 && $info[1] > 0) {
                    $logo = WEB_URL.DATA_FOLDER.'image/'.self::$cfg->logo;
                    $img_logo = '<img alt="{WEBTITLE}" src="'.$logo.'">';
                    Gcms::$site['logo'] = array(
                        '@type' => 'ImageObject',
                        'url' => $logo,
                        'width' => $info[0]
                    );
                }
            }
            if (is_file(ROOT_PATH.DATA_FOLDER.'image/site_logo.jpg')) {
                $info = @getimagesize(ROOT_PATH.DATA_FOLDER.'image/site_logo.jpg');
                if ($info && $info[0] > 0 && $info[1] > 0) {
                    Gcms::$site['logo'] = array(
                        '@type' => 'ImageObject',
                        'url' => WEB_URL.DATA_FOLDER.'image/site_logo.jpg',
                        'width' => $info[0]
                    );
                }
            }
            // หน้า home (เมนูรายการแรกสุด)
            $home = Gcms::$menu->homeMenu();
            if ($home) {
                $home->canonical = WEB_URL.'index.php';
                // breadcrumb หน้า home
                Gcms::$view->addBreadcrumb($home->canonical, $home->menu_text, $home->menu_tooltip, 'icon-home');
            }
            // โมดูลแรกสุด ใส่ลงใน Javascript
            Gcms::$view->addScript('var FIRST_MODULE = "'.Gcms::$module->getFirst().'";');
            // ตรวจสอบโมดูลที่เรียก
            $modules = Gcms::$module->checkModuleCalled($request->getQueryParams());
            if (!empty($modules)) {
                // โหลดโมดูลที่เรียก
                $page = createClass($modules->className)->{$modules->method}($request, $modules->module);
            }
            if (empty($page)) {
                // ไม่พบหน้าที่เรียก (index)
                $page = createClass('Index\Error\Controller')->init('index');
            }
            $favicon = is_file(ROOT_PATH.DATA_FOLDER.'image/favicon.ico') ? WEB_URL.DATA_FOLDER.'image/favicon.ico' : WEB_URL.'favicon.ico';
            // meta tag
            $meta = array(
                'generator' => '<meta name="generator" content="GCMS AJAX CMS design by https://gcms.in.th">',
                'og:title' => '<meta property="og:title" content="'.$page->topic.'">',
                'description' => '<meta name="description" content="'.$page->description.'">',
                'og:description' => '<meta property="og:description" content="'.$page->description.'">',
                'keywords' => '<meta name="keywords" content="'.$page->keywords.'">',
                'og:site_name' => '<meta property="og:site_name" content="'.strip_tags(self::$cfg->web_title).'">',
                'og:type' => '<meta property="og:type" content="article">',
                'shortcut_icon' => '<link rel="shortcut icon" href="'.$favicon.'" type="image/x-icon">',
                'icon' => '<link rel="icon" href="'.$favicon.'" type="image/x-icon">'
            );
            if (empty($page->image_src) && isset(Gcms::$site['logo'])) {
                $page->image_src = Gcms::$site['logo']['url'];
            }
            if (!empty($page->image_src)) {
                $info = getimagesize($page->image_src);
                if ($info) {
                    $meta['image_src'] = '<link rel="image_src" href="'.$page->image_src.'">';
                    $meta['og:image'] = '<meta property="og:image" content="'.$page->image_src.'">';
                    $meta['og:image:alt'] = '<meta property="og:image:alt" content="'.$page->topic.'">';
                    $meta['og:image:width'] = '<meta property="og:image:width" content="'.$info[0].'">';
                    $meta['og:image:height'] = '<meta property="og:image:height" content="'.$info[1].'">';
                    $meta['og:image:type'] = '<meta property="og:image:type" content="'.$info['mime'].'">';
                }
            }
            if (!empty(self::$cfg->facebook_appId)) {
                $meta['og:app_id'] = '<meta property="fb:app_id" content="'.self::$cfg->facebook_appId.'">';
            }
            if (isset($page->canonical)) {
                $meta['canonical'] = '<link rel="canonical" href="'.$page->canonical.'">';
                $meta['og:url'] = '<meta property="og:url" content="'.$page->canonical.'">';
            }
            if (!empty(self::$cfg->google_site_verification)) {
                $meta['google_site_verification'] = '<meta name="google-site-verification" content="'.self::$cfg->google_site_verification.'">';
            }
            if (!empty(self::$cfg->theme_color)) {
                $meta['manifest'] = '<link rel="manifest" href="'.WEB_URL.'manifest.php">';
                $meta['theme-color'] = '<meta name="theme-color" content="'.self::$cfg->theme_color.'">';
            }
            if (!Login::isAdmin()) {
                if (!empty(self::$cfg->google_ads_code)) {
                    $meta['adsense'] = '<script data-ad-client="ca-'.self::$cfg->google_ads_code.'" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>';
                }
                if (!empty(self::$cfg->google_tag)) {
                    $meta['gtag'] = '<script async src="https://www.googletagmanager.com/gtag/js?id='.self::$cfg->google_tag.'"></script>';
                    $meta['gtag'] .= '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","'.self::$cfg->google_tag.'");</script>';
                }
            }
            Gcms::$view->setMetas($meta);
            // ภาษาที่ติดตั้ง
            $languages = Template::create('', '', 'language');
            foreach (self::$cfg->languages as $lng) {
                $languages->add(array(
                    '/{LNG}/' => $lng
                ));
            }
            // เนื้อหา
            Gcms::$view->setContents(array(
                // content
                '/{CONTENT}/' => $page->detail,
                // โลโก
                '/{LOGO}/' => $img_logo,
                '/{BGLOGO}/' => $logo,
                // title
                '/{TITLE}/' => $page->topic,
                // ภาษาที่ติดตั้ง
                '/{LANGUAGES}/' => $languages->render()
            ));
            // เมนูหลัก
            Gcms::$view->setContents(Gcms::$menu->render(isset($page->menu) ? $page->menu : $page->module));
        }
        // ส่งออก เป็น HTML
        $response = new Response();
        if (isset($page->status) && $page->status == 404) {
            $response = $response->withStatus(404)->withAddedHeader('Status', '404 Not Found');
        }
        $response->withContent(Gcms::$view->renderHTML())->send();
    }
}
