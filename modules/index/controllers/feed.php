<?php
/**
 * @filesource modules/index/controllers/feed.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Feed;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * feed.rss
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
        $module = $request->get('module')->toString();
        if (preg_match('/^[a-z]+$/', $module)) {
            // ตัวแปรป้องกันการเรียกหน้าเพจโดยตรง
            define('MAIN_INIT', __FILE__);
            // create Response
            $response = new Response();
            // จำนวนที่ต้องการ ถ้าไม่กำหนด คืนค่า 10 รายการ
            $count = $request->get('count')->toInt();
            if ($count == 0) {
                $count = $request->get('rows')->toInt() * $request->get('cols')->toInt();
            }
            $count = $count <= 0 ? 10 : $count;
            // วันที่วันนี้
            $cdate = date('D, d M Y H:i:s +0700');
            $today = date('Y-m-d');
            // ตรวจสอบโมดูล
            $index = \Index\Feed\Model::getModule($module, $today);
            if ($index) {
                // XML
                $content = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
                $content .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
                $content .= '<channel>';
                $url = self::$cfg->module_url == 1 ? $index->module.'.rss' : 'feed.php?module='.$index->module;
                $content .= '<atom:link href="'.WEB_URL.$url.'" rel="self" type="application/rss+xml" />';
                $content .= '<title>'.$index->topic.'</title>';
                $content .= '<link>'.Gcms::createUrl($index->module).'</link>';
                $content .= '<description><![CDATA['.$index->description.']]></description>';
                $content .= "<pubDate>$cdate</pubDate>";
                $content .= "<lastBuildDate>$cdate</lastBuildDate>";
                $dir = ROOT_PATH.'modules/';
                if (is_file($dir.$index->owner.'/controllers/feed.php')) {
                    // module feed
                    include $dir.$index->owner.'/controllers/feed.php';
                    $content .= createClass(ucfirst($index->owner).'\Feed\Controller')->init($request, $index, $count, $today);
                }
                $content .= '</channel>';
                $content .= '</rss>';
                // send Response
                $response->withContent($content)
                    ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
                    ->send();
                exit;
            }
        }
        // not found
        new \Kotchasan\Http\NotFound();
    }
}
