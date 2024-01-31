<?php
/**
 * @filesource modules/personnel/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\View;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Template;
use Personnel\Index\Controller;

/**
 * แสดงข้อมูลบุคลากรรายบุคคล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงข้อมูลบุคลากรรายบุคคล
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ลิสต์ข้อมูล
        $index = \Personnel\View\Model::get($request, $index);
        if ($index) {
            // breadcrumb ของโมดูล
            $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
            if ($menu) {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $menu->menu_text, $menu->menu_tooltip);
            } else {
                Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module), $index->topic);
            }
            if ($index->category != '') {
                // breadcrumb ของหมวดหมู่
                $index->canonical = Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module, '', $index->category_id), $index->category);
            }
            // หน้านี้
            $index->canonical = Controller::url($index->module, $index->id);
            Gcms::$view->addBreadcrumb($index->canonical, $index->name);
            // image
            if (is_file(ROOT_PATH.DATA_FOLDER.'personnel/'.$index->picture)) {
                $img = WEB_URL.DATA_FOLDER.'personnel/'.$index->picture;
            } else {
                $img = WEB_URL.'modules/personnel/img/noimage.jpg';
            }
            // /personnel/view.html
            $template = Template::create('personnel', $index->module, 'view');
            $template->add(array(
                '/{NAME}/' => $index->name,
                '/{POSITION}/' => $index->position,
                '/{ADDRESS}/' => $index->address,
                '/{PHONE}/' => $index->phone,
                '/{EMAIL}/' => $index->email,
                '/{CATEGORY}/' => $index->category,
                '/{DETAIL}/' => $index->detail,
                '/{PICTURE}/' => $img,
                '/{MODULE}/' => $index->module
            ));
            // คืนค่า
            $index->detail = $template->render();
            return $index;
        }
        return null;
    }
}
