<?php
/**
 * @filesource modules/index/controllers/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Sitemap;

use Kotchasan\Http\Request;
use Kotchasan\Http\Response;
use Web\Gcms;

/**
 * sitemap.xml
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Display sitemap.xml
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // Variable to prevent direct page access
        define('MAIN_INIT', 'sitemap');
        // create Response
        $response = new Response();
        // XML
        $content = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $content .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $content .= ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9';
        $content .= ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
        // Today's date
        $cdate = date('Y-m-d');
        // view
        $view = new \Index\Sitemap\View();
        // Home page
        $content .= $view->render(WEB_URL, $cdate);
        $content .= $view->render(WEB_URL.'index.php', $cdate);
        // เมนู
        foreach (\Index\Menu\Model::queryAllMenus() as $item) {
            if (!empty($item->module_name)) {
                $content .= $view->render(Gcms::createUrl($item->module_name), $cdate);
            }
        }
        // Installed modules
        $modules = [];
        $owners = [];
        foreach (\Index\Sitemap\Model::getModules() as $item) {
            $modules[$item->id] = $item->module;
            $owners[$item->owner][] = $item->id;
            $content .= $view->render(Gcms::createUrl($item->module, '', 0, 0, ($item->language == '' ? '' : 'lang='.$item->language)), $cdate);
        }
        // modules
        $dir = ROOT_PATH.'modules/';
        $f = @opendir($dir);
        if ($f) {
            while (false !== ($owner = readdir($f))) {
                if (!in_array($owner, ['.', '..', 'index']) && !empty($owners[$owner]) && is_file($dir.$owner.'/controllers/sitemap.php')) {
                    include $dir.$owner.'/controllers/sitemap.php';
                    foreach (createClass(ucfirst($owner).'\Sitemap\Controller')->init($owners[$owner], $modules, $cdate) as $item) {
                        $content .= $view->render($item->url, $item->date);
                    }
                }
            }
            closedir($f);
        }
        $content .= '</urlset>';
        // send Response
        $response->withContent($content)
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->send();
    }
}
