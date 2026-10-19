<?php
/**
 * @filesource modules/event/views/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\View;

use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Single-event page — ports gcms241021 Event\View\View.
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
        if (!Gcms::$menu->isHomeMenu($index->index_id)) {
            Gcms::$view->addBreadcrumb(\Event\Index\Controller::url($index->module), $index->module_topic, $index->module_description);
        }
        Gcms::$view->addBreadcrumb(\Event\Index\Controller::url($index->module, 0, $index->begin_date), Date::format($index->begin_date, 'd M Y'));

        // breadcrumb ของหน้า
        $index->canonical = \Event\Index\Controller::url($index->module, (int) $index->id);
        Gcms::$view->addBreadcrumb($index->canonical, $index->topic);

        $template = Template::create($index->owner, $index->module, 'view');
        $template->add([
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic),
            '/{DETAIL}/' => Gcms::highlighter($index->detail),
            '/{MODULE}/' => $index->module,
            '/{DATE}/' => Date::format($index->begin_date, 'd M Y'),
            '/{DATEISO}/' => $index->begin_date,
            '/{FROM_TIME}/' => Language::replace('FROM_TIME', ['H:i' => $index->from_time]),
            '/{TO_TIME}/' => empty($index->end_date) || $index->end_date === '0000-00-00' ? '' : Language::replace('TO_TIME', ['H:i' => $index->to_time]),
            // the CSS variable --event-color of the header (gcms.css)
            '/{COLOR}/' => Text::color($index->color, '#4CAF50')
        ]);

        $index->detail = $template->render();

        return $index;
    }
}
