<?php
/**
 * @filesource modules/portfolio/views/lists.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Portfolio\Lists;

use Gcms\Gcms;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;
use Portfolio\Index\Controller;

/**
 * แสดงรายการ Portfolio
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายการ Portfolio
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ลิสต์ข้อมูล
        $index = \Portfolio\Lists\Model::get($request, $index);
        // /portfolio/listitem.html
        $listitem = Grid::create('portfolio', $index->module, 'listitem');
        // ไดเร็คทอรี่ของรูปภาพ
        $imgdir = ROOT_PATH.DATA_FOLDER.'portfolio/thumb_';
        $imgurl = WEB_URL.DATA_FOLDER.'portfolio/thumb_';
        // รายการ
        foreach ($index->items as $item) {
            $tags = array();
            foreach (explode(',', $item->keywords) as $k) {
                $tags[] = '<li><a href="'.Gcms::createUrl($index->module, '', 0, 0, "tag=$k").'">'.$k.'</a></li>';
            }
            $listitem->add(array(
                '/{ID}/' => $item->id,
                '/{SRC}/' => is_file($imgdir.$item->id.'.jpg') ? $imgurl.$item->id.'.jpg' : WEB_URL.'/modules/portfolio/img/nopicture.png',
                '/{URL}/' => Controller::url($index->module, $item->id),
                '/{TOPIC}/' => $item->title,
                '/{TAGS}/' => implode("\n", $tags),
                '/{DATE}/' => $item->create_date
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
        // มีการเลือก tag
        if (!empty($index->tag)) {
            $index->canonical = Gcms::createUrl($index->module, '', 0, 0, 'tag='.$index->tag);
            Gcms::$view->addBreadcrumb($index->canonical, $index->tag);
        }
        // current URL
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        // /portfolio/list.html
        $template = Template::create('portfolio', $index->module, 'list');
        $template->add(array(
            '/{LIST}/' => $listitem->hasItem() ? $listitem->render() : '<div class="error center">{LNG_Sorry, no information available for this item.}</div>',
            '/{COLS}/' => $index->cols,
            '/{TOPIC}/' => $index->topic,
            '/{DETAIL}/' => Gcms::showDetail($index->detail, true, false),
            '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page)
        ));
        // คืนค่า
        $index->detail = $template->render();
        return $index;
    }
}
