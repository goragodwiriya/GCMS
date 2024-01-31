<?php
/**
 * @filesource modules/documentation/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Index;

use Gcms\Gcms;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * แสดงผลโมดูล documentation
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงผลโมดูล documentation
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // query หมวด ถ้าไม่มีใช้หมวดแรกสุด
        $category = \Documentation\Index\Model::category((int) $index->module_id, $request->request('cat')->toInt());
        if ($category === null) {
            return createClass('Index\Error\Controller')->init('documentation');
        } else {
            // template
            $template = Template::create($index->owner, $index->module, 'list');
            // รายการ
            $listitem = Grid::create($index->owner, $index->module, 'item');
            foreach (\Documentation\Index\Model::get((int) $index->module_id, (int) $category->category_id) as $item) {
                $listitem->add(array(
                    '/{URL}/' => Controller::url($index->module, $item->alias, 0, $item->id),
                    '/{TOPIC}/' => $item->topic,
                    '/{DETAIL}/' => $item->description
                ));
            }
            // แทนที่ลงใน template
            $replace = array(
                '/{DETAIL}/' => Gcms::showDetail($index->detail, true, false),
                '/{LIST}/' => $listitem->hasItem() ? $listitem->render() : '<div class="error center">'.Language::get('Sorry, no information available for this item.').'</div>',
                '/{TOPIC}/' => $category->topic,
                '/{CATID}/' => $category->category_id,
                '/{MODULE}/' => $index->module
            );
            // แสดงผล
            $template->add($replace);
            // แทนที่ลงใน template
            $result = (object) array(
                'canonical' => Gcms::createUrl($index->module, '', $category->category_id),
                'detail' => $template->render(),
                'topic' => $category->topic.' '.$index->topic,
                'description' => $category->topic.' '.$index->description,
                'keywords' => $category->topic.' '.$index->keywords,
                'module' => $index->module,
                'menu' => $index->module
            );
            // breadcrumb
            if (!Gcms::$menu->isHome($index->index_id)) {
                $canonical = Gcms::createUrl($index->module);
                $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
                if ($menu) {
                    Gcms::$view->addBreadcrumb($canonical, $menu->menu_text, $menu->menu_tooltip);
                }
            }
            // breadcrumb ของหมวดที่เลือก
            Gcms::$view->addBreadcrumb($result->canonical, $category->topic);
            return $result;
        }
    }
}
