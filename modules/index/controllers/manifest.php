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
        $icons = [];
        $imgExt = self::$cfg->stored_img_type;
        // pwa_icon gets two separate entries: one for maskable, one for any
        // (combining them as 'maskable any' in a single entry is discouraged)
        $pwaIcon = DATA_FOLDER.'images/pwa_icon'.$imgExt;
        if (is_file(ROOT_PATH.$pwaIcon)) {
            $image_info = @getimagesize(ROOT_PATH.$pwaIcon);
            if ($image_info !== false) {
                $iconBase = [
                    'src' => WEB_URL.$pwaIcon,
                    'type' => $image_info['mime'],
                    'sizes' => $image_info[0].'x'.$image_info[1]
                ];
                $icons[] = array_merge($iconBase, ['purpose' => 'any']);
                $icons[] = array_merge($iconBase, ['purpose' => 'maskable']);
            }
        }
        // Additional icons with purpose 'any'
        $extraIcons = [
            DATA_FOLDER.'images/site_logo'.$imgExt,
            DATA_FOLDER.'images/company_logo'.$imgExt
        ];
        foreach ($extraIcons as $file) {
            if (is_file(ROOT_PATH.$file)) {
                $image_info = @getimagesize(ROOT_PATH.$file);
                if ($image_info !== false) {
                    $icons[] = [
                        'src' => WEB_URL.$file,
                        'type' => $image_info['mime'],
                        'sizes' => $image_info[0].'x'.$image_info[1],
                        'purpose' => 'any'
                    ];
                }
            }
        }
        $web_title = strip_tags(self::$cfg->web_title);
        $screenshots = [];
        // Wide screenshot (landscape) — desktop rich install UI
        // Must be at least 320×320; prefer screenshot.webp, fall back to site_logo.webp
        foreach ([DATA_FOLDER.'images/screenshot'.$imgExt, DATA_FOLDER.'images/site_logo'.$imgExt] as $wideFile) {
            if (is_file(ROOT_PATH.$wideFile)) {
                $image_info = @getimagesize(ROOT_PATH.$wideFile);
                if ($image_info !== false && $image_info[0] >= 320 && $image_info[1] >= 320) {
                    $screenshots[] = [
                        'src' => WEB_URL.$wideFile,
                        'type' => $image_info['mime'],
                        'sizes' => $image_info[0].'x'.$image_info[1],
                        'form_factor' => 'wide',
                        'label' => $web_title
                    ];
                    break;
                }
            }
        }
        // Narrow screenshot (portrait / square) — mobile rich install UI
        $narrowFile = DATA_FOLDER.'images/site_logo'.$imgExt;
        if (is_file(ROOT_PATH.$narrowFile)) {
            $image_info = @getimagesize(ROOT_PATH.$narrowFile);
            if ($image_info !== false && $image_info[0] >= 320 && $image_info[1] >= 320) {
                $screenshots[] = [
                    'src' => WEB_URL.$narrowFile,
                    'type' => $image_info['mime'],
                    'sizes' => $image_info[0].'x'.$image_info[1],
                    'form_factor' => 'narrow',
                    'label' => $web_title
                ];
            }
        }
        $json = [
            'id' => parse_url(WEB_URL, PHP_URL_PATH) ?: '/',
            'name' => $web_title,
            'lang' => Language::name(),
            'short_name' => $web_title,
            'description' => self::$cfg->web_description,
            'start_url' => WEB_URL,
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
            'theme_color' => self::$cfg->theme_color,
            'background_color' => '#ffffff',
            'orientation' => 'any',
            'prefer_related_applications' => false,
            'icons' => $icons,
            'screenshots' => $screenshots
        ];
        // Response
        $response = new \Kotchasan\Http\Response();
        $response->withHeaders([
            'Content-Type' => 'application/manifest+json; charset=utf-8'
        ])
            ->withContent(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->send();
    }
}
