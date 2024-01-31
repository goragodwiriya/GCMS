<?php
/**
 * @filesource modules/gallery/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\View;

use Gallery\Index\Controller;
use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * แสดงรูปภาพในอัลบัม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรูปภาพในอัลบัม
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ลิสต์ข้อมูล
        $index = \Gallery\View\Model::get($request, $index);
        if ($index) {
            // login
            $login = Login::isMember();
            $login_status = $login ? $login['status'] : -1;
            // breadcrumb ของโมดูล
            $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            } else {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $index->topic, $index->description);
            }
            // หน้านี้
            $index->canonical = Controller::url($index->module, $index->id);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
            // current URL
            $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
            if (in_array($login_status, $index->can_view)) {
                // /gallery/listitem.html
                $listitem = Grid::create('gallery', $index->module, 'listitem');
                // ลิสต์รายการ
                foreach ($index->items as $item) {
                    // image
                    if (is_file(ROOT_PATH.DATA_FOLDER.'gallery/'.$index->id.'/'.$item->image)) {
                        $thumb = WEB_URL.DATA_FOLDER.'gallery/'.$index->id.'/thumb_'.$item->image;
                        $img = WEB_URL.DATA_FOLDER.'gallery/'.$index->id.'/'.$item->image;
                    } else {
                        $thumb = WEB_URL.'modules/gallery/img/noimage.jpg';
                        $img = WEB_URL.'modules/gallery/img/noimage.jpg';
                    }
                    $listitem->add(array(
                        '/{ID}/' => $item->id,
                        '/{SRC}/' => $thumb,
                        '/{URL}/' => $img
                    ));
                }
                // /gallery/list.html
                $template = Template::create('gallery', $index->module, 'list');
                $template->add(array(
                    '/{LIST}/' => $listitem->render(),
                    '/{TOPIC}/' => $index->topic,
                    '/{DETAIL}/' => nl2br($index->detail),
                    '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
                    '/{MODULE}/' => $index->module,
                    '/{COLS}/' => $index->cols,
                    '/{WIDTH}/' => $index->icon_width,
                    '/{HEIGHT}/' => $index->icon_height,
                    '/{VISITED}/' => $index->visited,
                    '/{LASTUPDATE}/' => Date::format($index->last_update, 'd M Y')
                ));
                // คืนค่า
                $index->detail = $template->render();
            } else {
                // not login
                $replace = array(
                    '/{TOPIC}/' => $index->topic,
                    '/{DETAIL}/' => '<div class=error>{LNG_Members Only}</div>'
                );
                $index->detail = Template::create('gallery', $index->module, 'error')->add($replace)->render();
            }
            return $index;
        }
        return null;
    }
}
