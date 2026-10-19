<?php
/**
 * @filesource widgets/counter/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Counter\Controllers;

use Kotchasan\Language;
use Kotchasan\Template;

/**
 * Widget: site visit counter — page views for today, yesterday, this month
 * and all-time (see Widgets\Counter\Models\Index for why only `visited` is
 * used). Ports gcms241021 Widgets\Counter onto the current widget convention.
 * Mirrors widgets/search/controllers/index.php's shape.
 *
 * {WIDGET_COUNTER}
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * No automatic position injection; rendered via the {WIDGET_COUNTER} tag.
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
     * @param array $query_string  (unused — counter is site-wide)
     *
     * @return string
     */
    public function get($query_string)
    {
        $data = \Widgets\Counter\Models\Index::get();

        // Labels resolved in PHP (not via {LNG_} tokens) so the widget renders
        // correctly both on the frontend and in the Designer preview.
        $template = Template::createFromFile(ROOT_PATH.'widgets/counter/views/counter.html');
        $template->add([
            '/{L_TODAY}/' => Language::get('Today'),
            '/{TODAY}/' => number_format($data->today),
            '/{L_YESTERDAY}/' => Language::get('Yesterday'),
            '/{YESTERDAY}/' => number_format($data->yesterday),
            '/{L_MONTH}/' => Language::get('This month'),
            '/{MONTH}/' => number_format($data->month),
            '/{L_TOTAL}/' => Language::get('Total'),
            '/{TOTAL}/' => number_format($data->total)
        ]);

        return $template->render();
    }
}
