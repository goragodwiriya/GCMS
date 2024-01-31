<?php
/**
 * @filesource modules/index/controllers/manifest.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Manifest;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * manifest.json
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * manifest.json
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $web_title = strip_tags(self::$cfg->web_title);
        $json = array(
            'name' => $web_title,
            'short_name' => $web_title,
            'description' => self::$cfg->web_description,
            'start_url' => WEB_URL,
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'lang' => Language::name(),
            'background_color' => 'transparent',
            'theme_color' => self::$cfg->theme_color,
            'prefer_related_applications' => false,
            'icons' => array(),
            'screenshots' => array()
        );
        // Response
        $response = new \Kotchasan\Http\Response();
        $response->withHeaders(array(
            'Content-Type' => 'application/manifest+json; charset=utf-8'
        ))
            ->withContent(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->send();
    }
}
