<?php
/**
 * @filesource modules/gallery/views/album.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Album;

use Gallery\Index\Controller;
use Gcms\Gcms;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * แสดงรายการสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายการบทความ
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ลิสต์ข้อมูล
        $index = \Gallery\Album\Model::get($request, $index);
        // /gallery/albumitem.html
        $listitem = Grid::create('gallery', $index->module, 'albumitem');
        // รายการ
        foreach ($index->items as $item) {
            // image
            if (is_file(ROOT_PATH.DATA_FOLDER.'gallery/'.$item->id.'/thumb_'.$item->image)) {
                $img = WEB_URL.DATA_FOLDER.'gallery/'.$item->id.'/thumb_'.$item->image;
            } else {
                $img = WEB_URL.'modules/gallery/img/noimage.jpg';
            }
            $listitem->add(array(
                '/{ID}/' => $item->id,
                '/{SRC}/' => $img,
                '/{URL}/' => Controller::url($index->module, $item->id),
                '/{TOPIC}/' => $item->topic
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
            } else {
                Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
            }
        }
        // current URL
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        // /gallery/album.html
        $template = Template::create('gallery', $index->module, 'album');
        $template->add(array(
            '/{LIST}/' => $listitem->hasItem() ? $listitem->render() : '<div class="error center">{LNG_Sorry, no information available for this item.}</div>',
            '/{COLS}/' => $index->cols,
            '/{TOPIC}/' => $index->topic,
            '/{DETAIL}/' => Gcms::showDetail($index->detail, true, false),
            '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
            '/{MODULE}/' => $index->module,
            '/{WIDTH}/' => $index->icon_width,
            '/{HEIGHT}/' => $index->icon_height
        ));
        // คืนค่า
        $index->detail = $template->render();
        return $index;
    }
}
