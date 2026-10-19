<?php
/**
 * @filesource modules/index/controllers/robots.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Robots;

use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * robots.txt
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * แสดงผล robots.txt
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // create Response
        $response = new Response();

        $content = ['Sitemap: '.WEB_URL.'sitemap.xml'];

        $content[] = 'User-agent: *';
        $content[] = '# Disallow access to installer, admin and sensitive internal folders';
        $content[] = 'Disallow: /install/';
        $content[] = 'Disallow: /install.php';
        $content[] = 'Disallow: /admin/';
        $content[] = 'Disallow: /datas/';
        $content[] = 'Disallow: /Gcms/';
        $content[] = 'Disallow: /Kotchasan/';
        $content[] = 'Disallow: /modules/';
        $content[] = 'Disallow: /Now/';
        $content[] = 'Disallow: /Thaibluksms/';
        $content[] = 'Disallow: /line/';

        $content[] = '# Allow everything else';
        $content[] = 'Allow: /';

        // send Response
        $response->withContent(implode("\n", $content))
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->send();
    }
}
