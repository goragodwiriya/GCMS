<?php
/**
 * @filesource modules/download/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Index;

use Gcms\Gcms;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;
use Kotchasan\Text;

/**
 * แสดงรายการไฟล์ดาวน์โหลด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายการไฟล์ดาวน์โหลด
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // หมวดหมู่ที่เลือก
        $index->category_id = $request->request('cat')->toInt();
        // query ข้อมูล
        $index = \Download\Index\Model::getItems($request, $index);
        // หมวดหมู่
        $categories = \Index\Category\Model::categories($index->module_id);
        // /download/listitem.html
        $listitem = Grid::create('download', $index->module, 'listitem');
        foreach ($index->items as $item) {
            $listitem->add(array(
                '/{ID}/' => $item->id,
                '/{NAME}/' => $item->name,
                '/{EXT}/' => $item->ext,
                '/{ICON}/' => WEB_URL.'skin/ext/'.(is_file(ROOT_PATH.'skin/ext/'.$item->ext.'.png') ? $item->ext : 'file').'.png',
                '/{DETAIL}/' => $item->detail,
                '/{DATE}/' => $item->last_update,
                '/{DOWNLOADS}/' => number_format($item->downloads),
                '/{SIZE}/' => Text::formatFileSize($item->size),
                '/{CATEGORY}/' => empty($categories[$item->category_id]) ? '{LNG_Uncategorized}' : $categories[$item->category_id]
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
        // breadcrumb ของหมวดหมู่
        if (!empty($index->category_id) && !empty($categories[$index->category_id])) {
            $index->canonical = Gcms::createUrl($index->module, '', $index->category_id);
            Gcms::$view->addBreadcrumb(Gcms::createUrl($index->module, '', $index->category_id), $categories[$index->category_id]);
        }
        // current URL
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        // หมวดหมู่
        $categoryitem = Grid::create($index->owner, $index->module, 'categoryitem');
        if (!$categoryitem->isEmpty()) {
            foreach (array(0 => '{LNG_all items}') + $categories as $category_id => $topic) {
                $categoryitem->add(array(
                    '/{SELECT}/' => $category_id == $index->category_id ? 'selected' : '',
                    '/{TOPIC}/' => $topic,
                    '/{URL}/' => Gcms::createUrl($index->module, '', $category_id)
                ));
            }
        }
        // /download/list.html หรือ /download/empty.html ถ้าไม่มีข้อมูล
        $template = Template::create('download', $index->module, $listitem->hasItem() ? 'list' : 'empty');
        $template->add(array(
            '/{CATEGORIES}/' => $categoryitem->render(),
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
