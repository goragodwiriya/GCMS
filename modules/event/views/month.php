<?php
/**
 * @filesource modules/event/views/month.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Month;

use Kotchasan\Http\Request;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Month calendar page — renders the calendar container that
 * modules/event/script.js wires to the Now.js CalendarManager.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function render(Request $request, $index)
    {
        // breadcrumb ของโมดูล
        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = \Event\Index\Controller::url($index->module);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }

        $template = Template::create($index->owner, $index->module, 'month');
        $template->add([
            '/{TOPIC}/' => Text::htmlspecialchars((string) $index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars((string) $index->description),
            '/{DETAIL}/' => Gcms::highlighter($index->detail),
            '/{MODULE}/' => $index->module,
            '/{MODULE_ID}/' => (int) $index->module_id
        ]);

        $index->detail = $template->render();

        return $index;
    }
}
