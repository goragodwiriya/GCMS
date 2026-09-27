<?php
/**
 * @filesource widgets/stats/controllers/index.php
 *
 * Stats Widget Controller
 * Renders a Stats Section using data stored in datas/widgets/stats.json.
 * Each stat item displays an icon, a label, and an animated counter powered
 * by CounterComponent on the front-end.
 *
 * Usage placeholder : {WIDGET_STATS}
 * Or with a named set: {WIDGET_STATS_<name>}
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Stats\Controllers;

use Kotchasan\Text;

/**
 * Stats Widget — Frontend Renderer
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Return the rendered HTML for the stats widget.
     *
     * @param array $query_string  Supported keys:
     *                             name    – named stats set (default: 'default')
     *                             layout  – 'grid' | 'row' (default: 'grid')
     *                             columns – 2 | 3 | 4 | 5 (default: 4)
     *
     * @return string
     */
    public function get($query_string)
    {
        $name = isset($query_string['name']) ? preg_replace('/[^a-z0-9_]/', '', strtolower($query_string['name'])) : 'default';
        $columns = isset($query_string['columns']) ? (int) $query_string['columns'] : 4;
        $columns = max(2, min(6, $columns));

        // Load stats data
        $items = \Widgets\Stats\Models\Index::getItems($name);

        if (empty($items)) {
            return '';
        }

        $id = 'stats_'.uniqid();

        $html = [];
        $html[] = '<div class="widget-stats" id="'.$id.'">';

        foreach ($items as $i => $item) {
            $icon = !empty($item['icon']) ? Text::htmlspecialchars($item['icon']) : 'icon-star';
            $label = !empty($item['label']) ? Text::htmlspecialchars($item['label']) : '';
            $value = isset($item['value']) ? (float) $item['value'] : 0;
            $suffix = !empty($item['suffix']) ? Text::htmlspecialchars($item['suffix']) : '';
            $prefix = !empty($item['prefix']) ? Text::htmlspecialchars($item['prefix']) : '';
            $duration = !empty($item['duration']) ? (int) $item['duration'] : 2000;
            $format = !empty($item['format']) ? Text::htmlspecialchars($item['format']) : 'number';
            $separator = isset($item['separator']) ? Text::htmlspecialchars($item['separator']) : ',';
            $decimal = isset($item['decimal']) ? Text::htmlspecialchars($item['decimal']) : '.';
            $decimals = isset($item['decimals']) ? (int) $item['decimals'] : 0;
            $color = !empty($item['color']) ? Text::htmlspecialchars($item['color']) : '';
            $description = !empty($item['description']) ? Text::htmlspecialchars($item['description']) : '';
            $countMode = !empty($item['countMode']) ? Text::htmlspecialchars($item['countMode']) : 'up';
            $animation = !empty($item['animation']) ? Text::htmlspecialchars($item['animation']) : 'default';
            $delay = isset($item['delay']) ? (int) $item['delay'] : 0;
            $easing = !empty($item['easing']) ? Text::htmlspecialchars($item['easing']) : 'easeOutExpo';

            $styleAttr = $color ? ' style="--stat-color:'.$color.'"' : '';

            $html[] = '<div class="stat-card"'.$styleAttr.'>';
            $html[] = '  <div class="stat-icon '.$icon.'" data-editable="icon" data-editor-field-name="widget-stats-'.$name.'-icon-'.$i.'"></div>';
            $html[] = '    <div class="stat-number">';
            $html[] = '      <span';
            $html[] = '        data-component="counter"';
            $html[] = '        data-end="'.$value.'"';
            $html[] = '        data-duration="'.$duration.'"';
            $html[] = '        data-format="'.$format.'"';
            $html[] = '        data-separator="'.$separator.'"';
            $html[] = '        data-decimal="'.$decimal.'"';
            $html[] = '        data-decimal-places="'.$decimals.'"';
            if ($prefix) {
                $html[] = '        data-prefix="'.$prefix.'"';
            }

            if ($suffix) {
                $html[] = '        data-suffix="'.$suffix.'"';
            }

            $html[] = '        data-scroll-trigger="true"';
            if ($countMode !== 'up') {
                $html[] = '        data-count-mode="'.$countMode.'"';
            }
            if ($animation !== 'default') {
                $html[] = '        data-animation="'.$animation.'"';
            }
            if ($delay > 0) {
                $html[] = '        data-delay="'.$delay.'"';
            }
            $html[] = '        data-easing="'.$easing.'"';
            $html[] = '      ></span>';
            $html[] = '    </div>';
            $html[] = '    <div class="stat-label" data-i18n>'.$label.'</div>';
            if ($description) {
                $html[] = '    <div class="stat-description" data-i18n>'.$description.'</div>';
            }
            $html[] = '</div>';
        }

        $html[] = '</div>';

        return implode("\n", $html);
    }
}
