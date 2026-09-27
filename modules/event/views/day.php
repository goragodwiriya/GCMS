<?php
/**
 * @filesource modules/event/views/day.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Day;

use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Day-listing page — the events that begin on a chosen day.
 * Ports gcms241021 Event\Day\View.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * @param object $index
     *
     * @return object
     */
    public function render($index)
    {
        // breadcrumb ของโมดูล
        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = \Event\Index\Controller::url($index->module);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }
        Gcms::$view->addBreadcrumb(\Event\Index\Controller::url($index->module, 0, $index->date), Date::format($index->date, 'd M Y'));

        $listitem = Template::create($index->owner, $index->module, 'dayitem');
        foreach ($index->items as $item) {
            $listitem->add([
                '/{URL}/' => \Event\Index\Controller::url($index->module, (int) $item->id),
                '/{TOPIC}/' => Text::htmlspecialchars($item->topic),
                '/{DESCRIPTION}/' => Text::htmlspecialchars($item->description),
                '/{FROM_TIME}/' => Language::replace('FROM_TIME', ['H:i' => $item->from_time]),
                '/{TO_TIME}/' => empty($item->end_date) || $item->end_date === '0000-00-00' ? '' : Language::replace('TO_TIME', ['H:i' => $item->to_time]),
                // the CSS variable --event-color of the item (gcms.css)
                '/{COLOR}/' => Text::color($item->color, '#4CAF50')
            ]);
        }

        $list = $listitem->hasItem()
            ? $listitem->render()
            : Template::create($index->owner, $index->module, 'empty')->render();

        $template = Template::create($index->owner, $index->module, 'day');
        $template->add([
            '/{DATE}/' => Date::format($index->date, 'd M Y'),
            '/{DATEISO}/' => $index->date,
            '/{LIST}/' => $list,
            '/{MODULE}/' => $index->module,
            '/{TOPIC}/' => Text::htmlspecialchars((string) $index->topic)
        ]);

        $index->detail = $template->render();

        return $index;
    }
}
