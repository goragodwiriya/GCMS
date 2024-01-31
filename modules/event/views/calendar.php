<?php
/**
 * @filesource event/views/calendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Calendar;

use Gcms\Gcms;
use Kotchasan\Date;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;
use Kotchasan\Text;

/**
 * แสดงรายการบทความ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายการดาวน์โหลด
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // query ข้อมูล
        $index = \Event\Index\Model::getItems($request, $index);
        // /event/listitem.html
        $listitem = Grid::create('event', $index->module, 'listitem');
        foreach ($index->items as $item) {
            $listitem->add(array(
                '/{ID}/' => $item->id,
                '/{NAME}/' => $item->name,
                '/{EXT}/' => $item->ext,
                '/{ICON}/' => WEB_URL.'skin/ext/'.(is_file(ROOT_PATH.'skin/ext/'.$item->ext.'.png') ? $item->ext : 'file').'.png',
                '/{DETAIL}/' => $item->detail,
                '/{DATE}/' => Date::format($item->last_update),
                '/{DOWNLOADS}/' => number_format($item->downloads),
                '/{SIZE}/' => Text::formatFileSize($item->size)
            ));
        }
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
        // current URL
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        // /event/list.html หรือ /event/empty.html ถ้าไม่มีข้อมูล
        $template = Template::create('event', $index->module, $listitem->hasItem() ? 'list' : 'empty');
        $template->add(array(
            '/{TOPIC}/' => $index->topic,
            '/{DETAIL}/' => $index->detail,
            '/{LIST}/' => $listitem->render(),
            '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
            '/{MODULE}/' => $index->module
        ));
        // คืนค่า
        $index->detail = $template->render();
        return $index;
    }
}
