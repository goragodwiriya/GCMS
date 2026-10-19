<?php
/**
 * @filesource widgets/event/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Event\Controllers;

use Kotchasan\Date;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget: upcoming events from an event module.
 *
 * {WIDGET_EVENT module=calendar;count=5}
 *
 * Mirrors widgets/gallery/controllers/index.php's shape.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * No automatic position injection; rendered via the {WIDGET_EVENT} tag.
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
     * @param array $query_string  module=name, count=N
     *
     * @return string
     */
    public function get($query_string)
    {
        $module = empty($query_string['module']) ? 'event' : $query_string['module'];
        $index = Gcms::$module->findByModule($module);
        if (!$index) {
            return '';
        }

        $limit = isset($query_string['count']) ? (int) $query_string['count'] : 5;
        $limit = max(1, min(20, $limit));

        $events = \Widgets\Event\Models\Index::getUpcoming($index->module_id, $limit);
        if (empty($events)) {
            return '';
        }

        $listitem = Template::createFromFile(ROOT_PATH.'widgets/event/views/item.html');
        foreach ($events as $event) {
            $listitem->add([
                '/{ID}/' => (int) $event->id,
                '/{URL}/' => \Event\Index\Controller::url($module, (int) $event->id),
                '/{TOPIC}/' => Text::htmlspecialchars($event->topic),
                // the item's CSS variable --event-color (widgets/event/style.css)
                '/{COLOR}/' => \Kotchasan\Text::color($event->color, '#4CAF50'),
                '/{DAY}/' => Date::format($event->begin_date, 'd'),
                '/{MONTH}/' => Date::format($event->begin_date, 'M'),
                '/{DATE}/' => Date::format($event->begin_date, 'd M Y'),
                '/{DATEISO}/' => $event->begin_date,
                '/{TIME}/' => empty($event->begin_time) ? '' : $event->begin_time
            ]);
        }

        return '<div class="widget-event-upcoming">'.$listitem->render().'</div>';
    }
}
