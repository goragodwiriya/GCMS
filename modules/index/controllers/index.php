<?php
/**
 * @filesource modules/index/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Index;

use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;
use Web\Login;

/**
 * Controller for displaying web pages
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * @var mixed
     */
    private $isAdmin;

    /**
     * Main website page (index.html)
     * Returns HTML
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        if (!defined('DATA_FOLDER')) {
            // Domain not found, show 404 page
            \Index\Usernotfound\Controller::execute();
            exit;
        }
        // Variable to prevent direct page access
        define('MAIN_INIT', 'indexhtml');
        // Verify login (supports both JWT Token and Session)
        Login::create($request);
        // Language being used
        Language::name();
        // Set theme for template
        Template::init(self::$cfg->skin);
        $page = null;
        // website administrator
        $this->isAdmin = Login::isAdmin();
        // Tenant suspended (offline) or past its expire_date (unix timestamp,
        // matches the legacy gcms241021-derived market_customer schema) — the
        // public site is unavailable regardless of maintenance mode. Admins
        // can still preview the live site while logged in, same exception the
        // maintenance-mode check below already makes.
        $offline = !empty(self::$cfg->offline) && self::$cfg->offline == '1';
        $expired = !empty(self::$cfg->expire_date) && time() > (int) self::$cfg->expire_date;
        if (($offline || $expired) && !$this->isAdmin) {
            Gcms::$view = new \Index\Siteunavailable\View();
        } elseif (!empty(self::$cfg->show_maintenance) && !$this->isAdmin) {
            Gcms::$view = new \Index\Maintenance\View();
        } elseif (!empty(self::$cfg->show_intro) && str_replace([BASE_PATH, '/'], '', $request->getUri()->getPath()) == '') {
            Gcms::$view = new \Index\Intro\View();
        } else {
            // View for GCMS
            Gcms::$view = new \Web\View();
            // Retrieve login information
            $login = Login::isMember();
            // Load menu
            Gcms::$menu = \Index\Menu\Controller::init($login);
            // โหลด Counter
            $new_day = \Index\Counter\Model::init($request);
            // Load installed modules and can be used and Cron
            Gcms::$module = \Index\Module\Controller::init(Gcms::$menu, $new_day);
            // Website information
            $img_logo = $this->setupSiteInfo();
            // home page (first menu item)
            $home = Gcms::$menu->getHomeMenu();
            if ($home) {
                $home->canonical = WEB_URL.'index.php';
                // breadcrumb หน้า home
                Gcms::$view->addBreadcrumb($home->canonical, $home->menu_text, $home->menu_tooltip, 'icon-home');
            }
            // Check the called module
            $modules = Gcms::$module->checkModuleCalled($request->getQueryParams());
            // Load all widgets
            Gcms::$widget = \Index\Widget\Controller::load();
            if (!empty($modules)) {
                // Load the calling module
                $page = createClass($modules->className)->{$modules->method}($request, $modules->module);
            }
            if (empty($page)) {
                // The requested page (index) was not found.
                $page = \Index\Error\Controller::create()->init('index');
            }
            // Set Meta Tags
            $this->setupMetaTags($page);

            // Languages Menus
            $languages = Template::create('', '', 'language');
            foreach (self::$cfg->languages as $lng) {
                $languages->add([
                    '/{LNG}/' => $lng
                ]);
            }
            // content
            Gcms::$view->setContents([
                // Home page content
                '/{MAIN}/' => $page->detail,
                // logo
                '/{LOGO}/' => $img_logo,
                // Title
                '/{TITLE}/' => $page->topic,
                // Languages Menus
                '/{LANGUAGES}/' => $languages->render()
            ]);
        }
        // Export to HTML
        $this->sendResponse($page);
    }

    /**
     * Set up website information (Organization JSON-LD + header logo)
     */
    private function setupSiteInfo()
    {
        $company = self::$cfg->company ?? [];

        // Build Organization schema — prefer BookStore for bookstore CMS
        Gcms::$site = [
            '@type' => !empty(self::$cfg->site_type) ? self::$cfg->site_type : 'Organization',
            'name' => self::$cfg->web_title,
            'description' => self::$cfg->web_description ?? '',
            'url' => WEB_URL.'index.php'
        ];

        // Contact info from company config
        if (!empty($company['phone'])) {
            Gcms::$site['telephone'] = $company['phone'];
        }
        if (!empty($company['email'])) {
            Gcms::$site['email'] = $company['email'];
        }
        if (!empty($company['address'])) {
            Gcms::$site['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $company['address'],
                'addressCountry' => 'TH'
            ];
        }

        // Social profiles (sameAs)
        $sameAs = [];
        $facebookUrl = Gcms::facebookPageUrl(self::$cfg);
        if ($facebookUrl !== '') {
            $sameAs[] = $facebookUrl;
        }
        if (!empty(self::$cfg->line_id)) {
            $sameAs[] = 'https://line.me/ti/p/'.self::$cfg->line_id;
        }
        if (!empty($sameAs)) {
            Gcms::$site['sameAs'] = $sameAs;
        }

        // Logo: priority order — header logo → company_logo → site_logo
        $img_logo = '<span>{WEBTITLE}</span>';
        $imgType = self::$cfg->stored_img_type ?? '.webp';

        $logoSources = [];
        if (!empty(self::$cfg->header['logo'])) {
            $logoSources[] = ['path' => ROOT_PATH.DATA_FOLDER.'image/'.self::$cfg->header['logo'],
                'url' => WEB_URL.DATA_FOLDER.'image/'.self::$cfg->header['logo'],
                'forHeader' => true];
        }
        $logoSources[] = ['path' => ROOT_PATH.DATA_FOLDER.'image/company_logo'.$imgType,
            'url' => WEB_URL.DATA_FOLDER.'image/company_logo'.$imgType,
            'forHeader' => false];
        $logoSources[] = ['path' => ROOT_PATH.DATA_FOLDER.'image/site_logo'.$imgType,
            'url' => WEB_URL.DATA_FOLDER.'image/site_logo'.$imgType,
            'forHeader' => false];

        foreach ($logoSources as $src) {
            if (!is_file($src['path'])) {
                continue;
            }

            $info = @getimagesize($src['path']);
            if ($info && $info[0] > 0 && $info[1] > 0) {
                // First valid logo = header img (highest priority)
                if ($src['forHeader']) {
                    $img_logo = '<img src="'.$src['url'].'" alt="{WEBTITLE}" width="'.$info[0].'" height="'.$info[1].'">';
                }
                // Set JSON-LD logo only if not already set
                if (!isset(Gcms::$site['logo'])) {
                    Gcms::$site['logo'] = [
                        '@type' => 'ImageObject',
                        'url' => $src['url'],
                        'width' => $info[0],
                        'height' => $info[1]
                    ];
                }
                if ($src['forHeader']) {
                    break;
                }
                // header logo found — stop
            }
        }

        return $img_logo;
    }

    /**
     * Set up Meta Tags
     *
     * @param object $page
     */
    private function setupMetaTags($page)
    {
        $topic = Text::htmlspecialchars($page->topic ?? '');
        $description = Text::htmlspecialchars($page->description ?? '');
        $keywords = Text::htmlspecialchars($page->keywords ?? '');
        $siteName = Text::htmlspecialchars(strip_tags(self::$cfg->web_title));

        // Favicon
        $favicon = Gcms::favicon();

        // og:type — "website" for home/listing pages, "article" for content pages
        $ogType = (!empty($page->is_home) || empty($page->canonical) || $page->canonical === WEB_URL.'index.php')
            ? 'website'
            : 'article';

        // robots — noindex for 404 or pages that explicitly request it
        $robotsContent = (!empty($page->status) && $page->status == 404) || !empty($page->noindex)
            ? 'noindex, nofollow'
            : 'index, follow';

        // Locale — map language code to og:locale format
        $langMap = ['th' => 'th_TH', 'en' => 'en_US', 'zh' => 'zh_CN', 'ja' => 'ja_JP'];
        $ogLocale = $langMap[LANGUAGE] ?? strtolower(LANGUAGE).'_'.strtoupper(LANGUAGE);

        $meta = [
            'generator' => '<meta name="generator" content="GCMS AJAX CMS design by https://gcms.in.th">',
            'robots' => '<meta name="robots" content="'.$robotsContent.'">',
            'description' => '<meta name="description" content="'.$description.'">',
            'keywords' => !empty($keywords) ? '<meta name="keywords" content="'.$keywords.'">' : '',
            'og:locale' => '<meta property="og:locale" content="'.$ogLocale.'">',
            'og:type' => '<meta property="og:type" content="'.$ogType.'">',
            'og:title' => '<meta property="og:title" content="'.$topic.'">',
            'og:description' => '<meta property="og:description" content="'.$description.'">',
            'og:site_name' => '<meta property="og:site_name" content="'.$siteName.'">',
            'icon' => '<link rel="icon" href="'.$favicon['url'].'" type="'.$favicon['type'].'">'
        ];

        // Apple touch icon (PNG only — iOS ignores .ico; fallback to a PNG favicon)
        if (is_file(ROOT_PATH.DATA_FOLDER.'image/apple-touch-icon.png')) {
            $meta['apple-touch-icon'] = '<link rel="apple-touch-icon" href="'.WEB_URL.DATA_FOLDER.'image/apple-touch-icon.png">';
        } elseif ($favicon['type'] === 'image/png') {
            $meta['apple-touch-icon'] = '<link rel="apple-touch-icon" href="'.$favicon['url'].'">';
        }

        // OG image — use page image, fallback to site logo
        if (empty($page->image_src) && isset(Gcms::$site['logo']['url'])) {
            $page->image_src = Gcms::$site['logo']['url'];
        }
        if (!empty($page->image_src)) {
            // Only call getimagesize for local files to avoid slow network requests
            $localPath = str_replace(WEB_URL, ROOT_PATH, $page->image_src);
            $info = is_file($localPath) ? @getimagesize($localPath) : false;
            $meta['og:image'] = '<meta property="og:image" content="'.Text::htmlspecialchars($page->image_src).'">';
            $meta['og:image:alt'] = '<meta property="og:image:alt" content="'.$topic.'">';
            if ($info) {
                $meta['og:image:width'] = '<meta property="og:image:width" content="'.$info[0].'">';
                $meta['og:image:height'] = '<meta property="og:image:height" content="'.$info[1].'">';
                $meta['og:image:type'] = '<meta property="og:image:type" content="'.$info['mime'].'">';
            }
            $meta['twitter:card'] = '<meta name="twitter:card" content="summary_large_image">';
            $meta['twitter:image'] = '<meta name="twitter:image" content="'.Text::htmlspecialchars($page->image_src).'">';
        } else {
            $meta['twitter:card'] = '<meta name="twitter:card" content="summary">';
        }
        $meta['twitter:title'] = '<meta name="twitter:title" content="'.$topic.'">';
        $meta['twitter:description'] = '<meta name="twitter:description" content="'.$description.'">';

        // Canonical + og:url
        if (isset($page->canonical)) {
            $canonical = Text::htmlspecialchars($page->canonical);
            $meta['canonical'] = '<link rel="canonical" href="'.$canonical.'">';
            $meta['og:url'] = '<meta property="og:url" content="'.$canonical.'">';
            $meta['twitter:url'] = '<meta name="twitter:url" content="'.$canonical.'">';
        }

        // Facebook App ID (numeric only — skip a stray page URL saved by mistake)
        if (preg_match('/^\d+$/', (string) self::$cfg->facebook_appId)) {
            $meta['og:app_id'] = '<meta property="fb:app_id" content="'.Text::htmlspecialchars(self::$cfg->facebook_appId).'">';
        }

        // Google site verification
        if (!empty(self::$cfg->google_site_verification)) {
            $meta['google_site_verification'] = '<meta name="google-site-verification" content="'.Text::htmlspecialchars(self::$cfg->google_site_verification).'">';
        }

        // PWA
        if (!empty(self::$cfg->theme_color)) {
            $meta['manifest'] = '<link rel="manifest" href="'.WEB_URL.'manifest.php">';
            $meta['theme-color'] = '<meta name="theme-color" content="'.Text::htmlspecialchars(self::$cfg->theme_color).'">';
            $meta['serviceworker'] = '<script>if("serviceWorker" in navigator)navigator.serviceWorker.register("'.WEB_URL.'sw.js");</script>';
        }

        // Analytics & Ads (non-admin only)
        if (!$this->isAdmin) {
            if (!empty(self::$cfg->google_ads_code)) {
                $meta['adsense'] = '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-'.Text::htmlspecialchars(self::$cfg->google_ads_code).'" crossorigin="anonymous"></script>';
            }
            if (!empty(self::$cfg->google_tag)) {
                $gtag = Text::htmlspecialchars(self::$cfg->google_tag);
                $meta['gtag'] = '<script async src="https://www.googletagmanager.com/gtag/js?id='.$gtag.'"></script>';
                $meta['gtag'] .= '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","'.$gtag.'");</script>';
            }
        }

        // Module scripts/styles — CSS in head, JS deferred to footer
        $footerScripts = [];

        $themePath = ROOT_PATH.Template::get();
        $themeUrl = Template::getUrl();
        foreach (Gcms::$module->getInstalledOwners() as $owner => $modules) {
            if (is_file(ROOT_PATH.'modules/'.$owner.'/script.js')) {
                // ?v = mtime: *.js is cached for a week, a changed script must reach returning visitors
                $meta['script_modules_'.$owner] = '<script src="'.WEB_URL.'modules/'.$owner.'/script.js?v='.filemtime(ROOT_PATH.'modules/'.$owner.'/script.js').'"></script>';
            }
            if ($themePath !== ROOT_PATH && is_file($themePath.$owner.'/style.css')) {
                $meta['style_modules_'.$owner] = '<link rel="stylesheet" href="'.$themeUrl.$owner.'/style.css">';
            }
        }

        $path = ROOT_PATH.'widgets/';
        if (is_dir($path)) {
            foreach (scandir($path) as $name) {
                if ($name[0] !== '.') {
                    // ?v = mtime, same as the module scripts above
                    if (is_file($path.'/'.$name.'/script.js')) {
                        $meta['script_widgets_'.$name] = '<script src="'.WEB_URL.'widgets/'.$name.'/script.js?v='.filemtime($path.'/'.$name.'/script.js').'"></script>';
                    }
                    if (is_file($path.'/'.$name.'/style.css')) {
                        $meta['style_widgets_'.$name] = '<link rel="stylesheet" href="'.WEB_URL.'widgets/'.$name.'/style.css?v='.filemtime($path.'/'.$name.'/style.css').'">';
                    }
                }
            }
        }

        // B/W Mode — filter on <html>: the root element is exempt from the
        // containing block a filter creates, so position:fixed stays intact.
        // The Designer (body.gcms-home-edit-mode) needs true colours to edit.
        $bw = max(0, min(100, (int) self::$cfg->bw_mode));
        if ($bw > 0) {
            $meta['bw_mode'] = '<style>html{filter:grayscale('.$bw.'%)}html:has(>body.gcms-home-edit-mode){filter:none}</style>';
        }

        // Remove empty entries
        $meta = array_filter($meta);

        // Add Meta Tags to head
        Gcms::$view->setMetas($meta);

        // Add Javascripts to footer
        if (!empty($footerScripts)) {
            Gcms::$view->setFooterMetas($footerScripts);
        }
    }

    /**
     * Export data to HTML
     *
     * @param object $page
     */
    private function sendResponse($page)
    {
        $response = new Response();

        // Maintenance/Intro mode: $page is null, View handles its own rendering
        if ($page === null) {
            $response = $response->withContent(Gcms::$view->renderHTML());
            $response->send();
            return;
        }

        if (isset($page->status) && $page->status == 404) {
            $response = $response->withStatus(404)->withAddedHeader('Status', '404 Not Found');
        }
        $menu = isset($page->module) ? $page->module : 'home';
        $response = $response->withContent(Gcms::$view->renderWeb($menu));
        $response->send();
    }
}
