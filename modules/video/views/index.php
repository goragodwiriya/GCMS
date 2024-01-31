<?php
/**
 * @filesource modules/video/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Video\Index;

use Gcms\Gcms;
use Kotchasan\Date;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;

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
        $index = \Video\Index\Model::getItems($request, $index);
        // /video/listitem.html
        $listitem = Grid::create('video', $index->module, 'listitem');
        // รายการ
        foreach ($index->items as $item) {
            $listitem->add(array(
                '/{ID}/' => $item->id,
                '/{TOPIC}/' => $item->topic,
                '/{DESCRIPTION}/' => $item->description,
                '/{PICTURE}/' => is_file(ROOT_PATH.DATA_FOLDER.'video/'.$item->youtube.'.jpg') ? WEB_URL.DATA_FOLDER.'video/'.$item->youtube.'.jpg' : WEB_URL.'modules/video/img/nopicture.jpg',
                '/{YOUTUBE}/' => $item->youtube,
                '/{DATE}/' => Date::format($item->last_update),
                '/{DATEISO}/' => date(DATE_ISO8601, $item->last_update),
                '/{VIEWS}/' => number_format($item->views)
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
            } elseif ($index->topic != '') {
                Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
            }
        }
        // current URL
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        // /video/list.html หรือ /video/empty.html ถ้าไม่มีข้อมูล
        $template = Template::create('video', $index->module, $listitem->hasItem() ? 'list' : 'empty');
        $template->add(array(
            '/{TOPIC}/' => $index->topic,
            '/{DETAIL}/' => $index->detail,
            '/{LIST}/' => $listitem->render(),
            '/{COLS}/' => $index->cols,
            '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
            '/{MODULE}/' => $index->module
        ));
        // คืนค่า
        $index->detail = $template->render();
        return $index;
    }
}
