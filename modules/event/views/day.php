<?php
/**
 * @filesource event/views/day.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Day;

use Gcms\Gcms;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * แสดงรายการข่าว
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงปฎิทินรายวัน
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        $index = \Event\Day\Model::get($request, $index);
        if ($index) {
            // breadcrumb ของโมดูล
            if (Gcms::$menu->isHome($index->index_id)) {
                $index->canonical = WEB_URL.'index.php';
            } else {
                $index->canonical = Gcms::createUrl($index->module);
                $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
                if ($menu) {
                    Gcms::$view->addBreadcrumb($index->canonical, $menu->menu_text, $menu->menu_tooltip);
                }
            }
            Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module, '', 0, 0, 'd='.$index->date), Date::format($index->date, 'd M Y'));
            // /event/dayitem.html
            $listitem = Template::create('event', $index->module, 'dayitem');
            foreach ($index->items as $item) {
                $listitem->add(array(
                    '/{URL}/' => Gcms::createUrl($index->module, '', 0, 0, 'id='.$item->id),
                    '/{TOPIC}/' => $item->topic,
                    '/{DESCRIPTION}/' => $item->description,
                    '/{FROM_TIME}/' => Language::replace('FROM_TIME', array('H:i' => $item->from)),
                    '/{TO_TIME}/' => $item->end_date == '0000-00-00' ? '' : Language::replace('TO_TIME', array('H:i' => $item->to)),
                    '/{COLOR}/' => $item->color
                ));
            }
            // /event/day.html
            $template = Template::create('event', $index->module, 'day');
            $template->add(array(
                '/{DATE}/' => $index->date,
                '/{LIST}/' => $listitem->render(),
                '/{MODULE}/' => $index->module,
                '/{TOPIC}/' => $index->topic
            ));
            $index->detail = $template->render();
            return $index;
        }
        return null;
    }
}
