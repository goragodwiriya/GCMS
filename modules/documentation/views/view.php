<?php
/**
 * @filesource modules/documentation/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\View;

use Documentation\Index\Controller;
use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
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
     * แสดงผลเนื้อหา documentation
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ค่าที่ส่งมา
        $index->id = $request->get('id')->toInt();
        $index->alias = $request->get('alias')->text();
        // อ่านรายการที่เลือก
        $index = \Documentation\View\Model::get($index);
        if ($index && ($index->published || Login::isAdmin())) {
            // /documentation/view.html
            $detail = Template::create('documentation', $index->module, 'view');
            // แทนที่ลงใน template
            $replace = array(
                '/{DETAIL}/' => Gcms::showDetail($index->detail, true, false),
                '/{TOPIC}/' => $index->topic,
                '/{QID}/' => $index->id,
                '/{CATID}/' => $index->category_id,
                '/{MODULE}/' => $index->module
            );
            // แสดงผล
            $detail->add($replace);
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
            // breadcrumb ของหมวดหมู่
            if (!empty($index->category_id)) {
                // breadcrumb ของหมวดที่เลือก
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module, '', $index->category_id), $index->category);
                // breadcrumb ของหน้า
                $index->canonical = Controller::url($index->module, $index->alias, $index->id);
                Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
            }
            // AMP
            Gcms::$view->metas['amphtml'] = '<link rel="amphtml" href="'.WEB_URL.'amp.php?module='.$index->module.'&amp;id='.$index->id.'">';
            // JSON-LD
            Gcms::$view->setJsonLd(\Documentation\Jsonld\View::generate($index));
            // คืนค่า
            return (object) array(
                'canonical' => $index->canonical,
                'module' => $index->module,
                'topic' => $index->topic,
                'description' => $index->description,
                'keywords' => $index->keywords,
                'detail' => $detail->render()
            );
        }
        // 404
        return createClass('Index\Error\Controller')->init('documentation');
    }
}
