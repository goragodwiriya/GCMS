<?php
/**
 * @filesource widgets/search/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Search\Controllers;

use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;

/**
 * Widget: site-wide search box.
 *
 * {WIDGET_SEARCH}  — or  {WIDGET_SEARCH module=search}
 *
 * Ports gcms241021 Widgets\Search onto the current widget convention. The
 * form is a plain GET to index.php?module=search&q=... (the same site search
 * page routed by Index\Module\Controller::checkModuleCalled). Mirrors
 * widgets/share/controllers/index.php's shape.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * No automatic position injection; rendered via the {WIDGET_SEARCH} tag.
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
     * @param array $query_string  module=search (optional target module)
     *
     * @return string
     */
    public function get($query_string)
    {
        $module = empty($query_string['module']) ? 'search' : preg_replace('/[^a-z0-9]/', '', (string) $query_string['module']);
        if ($module === '') {
            $module = 'search';
        }

        // Pre-fill with the current query when the widget is shown on the
        // search results page. Resolved in PHP (not via {LNG_}/{WEBURL}
        // tokens) so it renders correctly through api/index/widgets/render too.
        $rawQuery = filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW);
        $query = Text::htmlspecialchars(strip_tags((string) $rawQuery));

        $template = Template::createFromFile(ROOT_PATH.'widgets/search/views/search.html');
        $template->add([
            '/{ID}/' => uniqid('sch'),
            '/{ACTION}/' => WEB_URL.'index.php',
            '/{MODULE}/' => $module,
            '/{QUERY}/' => $query,
            '/{PLACEHOLDER}/' => Language::get('Search'),
            '/{BTN_TITLE}/' => Language::get('Search')
        ]);

        return $template->render();
    }
}
