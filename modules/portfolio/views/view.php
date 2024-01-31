<?php
/**
 * @filesource modules/portfolio/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Portfolio\View;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * แสดงรายการที่เลือก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายการที่เลือก
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // query รายการที่เลือก
        $index = \Portfolio\View\Model::get($request, $index);
        if ($index) {
            // breadcrumb ของโมดูล
            $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            } else {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $index->topic, $index->description);
            }
            // หน้านี้
            $index->canonical = \Portfolio\Index\Controller::url($index->module, $index->id);
            Gcms::$view->addBreadcrumb($index->canonical, $index->title);
            // tags
            $tags = array();
            foreach (explode(',', $index->keywords) as $k) {
                $tags[] = '<li><a href="'.Gcms::createUrl($index->module, '', 0, 0, "tag=$k").'">'.$k.'</a></li>';
            }
            // /portfolio/view.html
            $template = Template::create('portfolio', $index->module, 'view');
            $template->add(array(
                '/{TOPIC}/' => $index->title,
                '/{DETAIL}/' => Gcms::showDetail($index->detail, true),
                '/{IMG}/' => WEB_URL.DATA_FOLDER.'portfolio/'.$index->image,
                '/{DATE}/' => $index->create_date,
                '/{VISITED}/' => number_format($index->visited),
                '/{TAGS}/' => implode("\n", $tags),
                '/{WEBSITE}/' => $index->url == '' ? '' : '<a href="'.$index->url.'" target=_blank>'.$index->url.'</a>'
            ));
            // คืนค่า
            $index->detail = $template->render();
            return $index;
        }
        return null;
    }
}
