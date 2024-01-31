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

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

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
     * แสดงผล sitemap.xml
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // ตัวแปรป้องกันการเรียกหน้าเพจโดยตรง
        define('MAIN_INIT', 'sitemap');
        // create Response
        $response = new Response();
        // XML
        $content = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $content .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $content .= ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9';
        $content .= ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
        // วันนี้
        $cdate = date('Y-m-d');
        // view
        $view = new \Index\Sitemap\View();
        // หน้าหลัก
        $content .= $view->render(WEB_URL.'index.php', $cdate);
        // โมดูลที่ติดตั้งแล้ว
        $modules = array();
        $owners = array();
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
                if (!in_array($owner, array('.', '..', 'index', 'css', 'js')) && !empty($owners[$owner]) && is_file($dir.$owner.'/controllers/sitemap.php')) {
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
