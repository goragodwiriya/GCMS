<?php
/**
 * @filesource Web/View.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Web;

use Kotchasan\Text;

/**
 * View base class for GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\BaseView
{
    /**
     * List of breadcrumb items
     *
     * @var array
     */
    private $breadcrumbs = [];

    /**
     * Add breadcrumb
     *
     * @param string|null $url     Link. If null, only text will be displayed.
     * @param string      $menu    Text to display in breadcrumb
     * @param string      $tooltip (optional) Tooltip
     * @param string      $class   (optional) Class for this link
     */
    public function addBreadcrumb($url, $menu, $tooltip = '', $class = '')
    {
        $menu = strip_tags(htmlspecialchars_decode($menu, ENT_NOQUOTES));
        $tooltip = $tooltip == '' ? $menu : $tooltip;
        // Escape class for security
        $class = Text::htmlspecialchars($class);
        // HTML-encode the label for the HTML body context (the plain $menu is
        // kept for the JSON-LD value, which json_encode escapes separately).
        $menuHtml = Text::htmlspecialchars($menu);
        if ($url) {
            // Security: Validate URL scheme to prevent XSS
            $url = self::sanitizeUrl($url);
            $this->breadcrumbs_jsonld[] = ['@id' => $url, 'name' => $menu];
            $this->breadcrumbs[] = '<li><a class="'.$class.'" href="'.$url.'" title="'.Text::htmlspecialchars($tooltip).'"><span>'.$menuHtml.'</span></a></li>';
        } else {
            $this->breadcrumbs_jsonld[] = ['name' => $menu];
            $this->breadcrumbs[] = '<li><span class="'.$class.'" title="'.Text::htmlspecialchars($tooltip).'">'.$menuHtml.'</span></li>';
        }
    }

    /**
     * Sanitize URL to prevent XSS attacks
     *
     * @param string $url
     * @return string
     */
    private static function sanitizeUrl($url)
    {
        // Parse URL to check scheme
        $parsed = parse_url($url);

        // Allow only safe schemes
        $allowedSchemes = ['http', 'https', 'mailto', 'tel', ''];

        if (isset($parsed['scheme'])) {
            $scheme = strtolower($parsed['scheme']);
            if (!in_array($scheme, $allowedSchemes)) {
                // Dangerous scheme detected (e.g., javascript:, data:, vbscript:)
                return '#';
            }
        }

        // Encode special characters to prevent attribute escape
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Check if text is empty. If $text is not empty, add $text to $array.
     *
     * @param string $text   Text to check
     * @param array  $array  Array to receive values from $text
     * @param string $prefix Text to prepend to $text if it is not empty (optional)
     */
    public static function checkEmpty($text, &$array, $prefix = '')
    {
        if ($text != '') {
            $array[] = $prefix.$text;
        }
    }

    /**
     * Convert date {DATE 0123456789 d M Y} or {DATE 2016-01-01 12:00:00 d M Y H:i:s}
     * Date in mktime numeric format only
     * Date in YYYY-mm-dd H:i:s format from MySQL (with or without time)
     * If no format is specified, the language format will be used
     *
     * @param array $matches
     *
     * @return string
     */
    public static function formatDate($matches)
    {
        if (!empty($matches[1])) {
            return \Kotchasan\Date::format($matches[1], isset($matches[4]) ? $matches[4] : null);
        }
        return '';
    }

    /**
     * Render Widget
     *
     * @param array $matches
     *
     * @return string
     */
    public static function getWidgets($matches)
    {
        $params = [
            'owner' => strtolower($matches[1])
        ];
        // A line break inside the tag is never data — it is an HTML formatter
        // wrapping a long tag in the theme file. Folded back to a space, or a
        // wrapped value would not match below and the param would be dropped.
        $query = empty($matches[3]) ? '' : trim(preg_replace('/[ \t]*(?:[\r\n]+[ \t]*)+/', ' ', $matches[3]));
        if ($query !== '') {
            $items = explode(';', $query);
            if (count($items) > 1) {
                foreach ($items as $item) {
                    if (preg_match('/^([^=]+)=(.+)$/', trim($item), $itemMatches)) {
                        $params[trim($itemMatches[1])] = $itemMatches[2];
                    }
                }
            } elseif (preg_match('/^([^=]+)=(.+)$/', $query, $itemMatches)) {
                $params[trim($itemMatches[1])] = $itemMatches[2];
            } else {
                $params['module'] = $query;
            }
        }
        $className = '\\Widgets\\'.ucfirst(strtolower($matches[1])).'\\Controllers\\Index';
        if (method_exists($className, 'get')) {
            return createClass($className)->get($params);
        }
        return '';
    }

    /**
     * Return URL of member's avatar
     *
     * @param int $id
     *
     * @return string
     */
    public static function usericon($id)
    {
        if (is_file(ROOT_PATH.DATA_FOLDER.'avatar/'.$id.self::$cfg->stored_img_type)) {
            return WEB_URL.DATA_FOLDER.'avatar/'.$id.self::$cfg->stored_img_type;
        } else {
            return WEB_URL.'images/no-image.webp';
        }
    }

    /**
     * Export to HTML
     *
     * @param string|null $menu
     * @param string|null $template HTML Template If not specified (null), index.html will be used.
     *
     * @return string
     */
    public function renderWeb($menu, $template = null)
    {
        // Content
        $contents = [];

        // Widget placeholders
        if (isset(Gcms::$widget)) {
            // sidebar (default)
            $contents['/{SIDEBAR}/'] = Gcms::$widget->get('sidebar');
            // sidebar (3 column)
            $contents['/{XSIDEBAR}/'] = Gcms::$widget->get('xsidebar');
            // widgets
            $contents['/{WIDGET_([A-Z]+)([_\s]+([^}]+))?}/e'] = '\Web\View::getWidgets(array(1=>"$1",3=>"$3"))';
        }

        // breadcrumbs
        $contents['/{BREADCRUMBS}/'] = implode('', $this->breadcrumbs);
        // Font size
        $contents['/{FONTSIZE}/'] = '<a class="font_size small" title="{LNG_change font small}">A<sup>-</sup></a><a class="font_size normal" title="{LNG_change font normal}">A</a><a class="font_size large" title="{LNG_change font large}">A<sup>+</sup></a>';
        // Language
        $contents['/{LNG_([^}]+)}/e'] = '\Kotchasan\Language::parse(array(1=>"$1"))';
        // Date
        $contents['/{DATE\s([0-9\-]+(\s[0-9:]+)?)?(\s([^}]+))?}/e'] = '\Web\View::formatDate(array(1=>"$1",4=>"$4"))';
        // Current language
        $contents['/{LANGUAGE}/'] = LANGUAGE;
        // class for body
        $contents['/{BODYCLASS}/'] = empty(self::$cfg->body['bodyClass']) ? '' : self::$cfg->body['bodyClass'];
        // GCMS version
        $contents['/{VERSION}/'] = isset(self::$cfg->version) ? self::$cfg->version : '';
        // File version number
        $contents['/{REV}/'] = empty(self::$cfg->reversion) ? '' : self::$cfg->reversion;
        // Theme stylesheet: .htaccess serves *.css with a one-week max-age and
        // every theme links it as {WEBURL}{SKIN}css/styles.css, so an edited
        // theme stylesheet (e.g. rewritten by the AI Theme Generator) stayed
        // invisible until the cache expired.
        // Key the URL on the file's mtime — it changes exactly when the file does.
        $stylesFile = ROOT_PATH.\Kotchasan\Template::get().'css/styles.css';
        if (is_file($stylesFile)) {
            $contents['/{SKIN}css\/styles\.css(?![\w?])/'] = \Kotchasan\Template::get().'css/styles.css?v='.filemtime($stylesFile);
        }
        // The shared stylesheets/script every theme links without a version
        // (themes/gcms.css, Now/dist/now.core.min.css|js) have the same
        // one-week max-age — a deployed CSS or framework fix stayed unseen by
        // returning visitors. Key them on their mtime as well.
        foreach (['themes/gcms.css', 'Now/dist/now.core.min.css', 'Now/dist/now.core.min.js'] as $asset) {
            if (is_file(ROOT_PATH.$asset)) {
                $contents['/{WEBURL}'.preg_quote($asset, '/').'(?![\w?])/'] = WEB_URL.$asset.'?v='.filemtime(ROOT_PATH.$asset);
            }
        }

        if (isset(Gcms::$menu)) {
            // Menu
            Gcms::$menu->render($menu, $contents);
        }
        // Insert into Template
        parent::setContents($contents);
        // JSON-LD (changes from page to page, see renderMain())
        if (!empty($this->jsonld)) {
            $this->metas['JsonLd'] = '<script type="application/ld+json" data-page-meta>'.json_encode($this->jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'</script>';
        }
        return parent::renderHTML($template);
    }

    /**
     * Render only what PageNavigator swaps when a page loads without a reload:
     * the inside of the theme's <main id="main"> (sidebar included, if the theme
     * puts it there) rendered exactly as in a full page, and the head tags that
     * change from page to page (marked data-page-meta).
     *
     * @param string|null $menu
     *
     * @return array|null ['content' => string, 'head' => string], null when the theme has no <main id="main">
     */
    public function renderMain($menu)
    {
        $template = \Kotchasan\Template::load('', '', 'index');
        if (!preg_match('/<main\b[^>]*\bid=["\']main["\'][^>]*>(.*?)<\/main>/is', $template, $match)) {
            return null;
        }
        // No <head> in this template, so renderHTML() leaves the meta tags in $this->metas
        $content = $this->renderWeb($menu, $match[1]);
        $head = [];
        foreach ($this->metas as $tag) {
            if (strpos($tag, ' data-page-meta') !== false) {
                $head[] = $tag;
            }
        }

        return [
            'content' => $content,
            'head' => implode("\n", $head)
        ];
    }

    /**
     * Convert a number of columns to a 12-grid size string.
     *
     * This method maps a requested number of columns (commonly 1..4) to the
     * corresponding grid size used by a 12-column layout (e.g. Bootstrap).
     *
     * Examples:
     *  - 1 => '12'
     *  - 2 => '6'
     *  - 3 => '4'
     *  - 4 => '3'
     *
     * For values outside common 1..4 range, the method will attempt a best-effort
     * conversion: if 12 is divisible by the number of columns the exact size is
     * returned (as string). Otherwise it returns the floored integer result of
     * 12 / columns (at least '1'). If the input is empty/invalid the provided
     * default is returned.
     *
     * @param int|string|null $value  Number of columns (1..n)
     * @param string $default        Default grid size string to return when mapping fails
     * @return string
     */
    public static function columnsToGridSize($value, $default = '4')
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $cols = [
            1 => '12',
            2 => '6',
            3 => '4',
            4 => '3'
        ];

        // accept numeric-like values
        $intVal = (int) $value;
        if ($intVal <= 0) {
            return $default;
        }

        if (isset($cols[$intVal])) {
            return $cols[$intVal];
        }

        // If 12 divides evenly by the requested columns, use that exact value
        if (12 % $intVal === 0) {
            return (string) (12 / $intVal);
        }

        // Best-effort: floor the division but ensure at least '1'
        $computed = (int) floor(12 / $intVal);
        if ($computed < 1) {
            $computed = 1;
        }
        return (string) $computed;
    }
}
