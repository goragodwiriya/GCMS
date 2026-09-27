<?php
/**
 * @filesource widgets/language/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Language\Controllers;

use Kotchasan\Language;
use Web\Gcms;

/**
 * Widget: frontend language switcher.
 *
 * {WIDGET_LANGUAGE}
 *
 * Ports gcms241021's {LANGUAGES} flag menu onto the widget convention so the
 * Designer can place it. Each link reloads the page being viewed with
 * ?lang=xx — Kotchasan\Language reads it, stores the my_lang cookie, and
 * menus, pages and <html lang> (which js/main.js follows) switch with it.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Each language's name in its own language, used as the link title.
     * A code not listed here falls back to the code itself.
     */
    const NAMES = [
        'th' => 'ไทย',
        'en' => 'English',
        'lo' => 'ລາວ',
        'my' => 'မြန်မာ',
        'km' => 'ខ្មែរ',
        'vi' => 'Tiếng Việt',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어'
    ];

    /**
     * No automatic position injection; rendered via the {WIDGET_LANGUAGE} tag.
     *
     * @param object $obj
     * @param array  $item
     *
     * @return void
     */
    public static function widget($obj, $item)
    {
    }

    /**
     * Display Widget
     *
     * @param array $query_string
     *
     * @return string
     */
    public function get($query_string)
    {
        $languages = array_filter(Gcms::installedLanguage(), static fn($lng) => preg_match('/^[a-z]{2}$/', $lng));
        if (count($languages) < 2) {
            return '';
        }

        // On a page the links keep its query (module, id, page …) and only swap
        // lang. The Designer preview renders through the widgets API, whose own
        // query means nothing to the page, so it starts clean.
        $params = [];
        if (Gcms::$view instanceof \Web\View) {
            parse_str((string) self::$request->getUri()->getQuery(), $params);
        }
        unset($params['lang']);

        $current = Language::name();
        $links = '';
        foreach ($languages as $lng) {
            $params['lang'] = $lng;
            $href = htmlspecialchars('?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986), ENT_QUOTES);
            $name = htmlspecialchars(self::NAMES[$lng] ?? strtoupper($lng), ENT_QUOTES);
            $flag = is_file(ROOT_PATH.'language/'.$lng.'.gif')
                ? '<img src="'.WEB_URL.'language/'.$lng.'.gif" alt="" width="16" height="11">'
                : '';
            $active = $lng === $current ? ' class="active" aria-current="true"' : '';
            $links .= '<a href="'.$href.'" hreflang="'.$lng.'" lang="'.$lng.'" title="'.$name.'"'.$active.'>'.$flag.'<span>'.strtoupper($lng).'</span></a>';
        }

        return '<nav class="widget-language" aria-label="'.htmlspecialchars(Language::get('Language'), ENT_QUOTES).'">'.$links.'</nav>';
    }
}
