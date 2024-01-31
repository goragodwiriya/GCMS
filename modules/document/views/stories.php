<?php
/**
 * @filesource modules/document/views/stories.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Stories;

use Document\Index\Controller;
use Gcms\Gcms;
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
     * แสดงรายการบทความ
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // วันที่สำหรับเครื่องหมาย new
        $valid_date = time() - $index->new_date;
        // /document/listitem.html
        $listitem = Grid::create('document', $index->module, 'listitem');
        // รูปภาพ defalt
        if (is_file(ROOT_PATH.DATA_FOLDER.'document/default_icon.png')) {
            $default_icon = WEB_URL.DATA_FOLDER.'document/default_icon.png';
        } elseif (isset($index->default_icon) && is_file(ROOT_PATH.$index->default_icon)) {
            $default_icon = WEB_URL.$index->default_icon;
        } else {
            $default_icon = WEB_URL.'modules/document/img/default_icon.png';
        }
        // ลิสต์รายการ
        foreach ($index->items as $item) {
            if (!empty($item->picture) && is_file(ROOT_PATH.DATA_FOLDER.'document/'.$item->picture)) {
                $thumb = WEB_URL.DATA_FOLDER.'document/'.$item->picture;
            } elseif (!empty($index->icon) && is_file(ROOT_PATH.DATA_FOLDER.'document/'.$index->icon)) {
                $thumb = WEB_URL.DATA_FOLDER.'document/'.$index->icon;
            } else {
                $thumb = $default_icon;
            }
            if ((int) $item->create_date > $valid_date && empty($item->comment_date)) {
                $icon = ' new';
            } elseif ((int) $item->last_update > $valid_date || (int) $item->comment_date > $valid_date) {
                $icon = ' update';
            } else {
                $icon = '';
            }
            $listitem->add(array(
                '/{ID}/' => $item->id,
                '/{PICTURE}/' => $thumb,
                '/{URL}/' => Controller::url($item->module, $item->alias, $item->id),
                '/{TOPIC}/' => $item->topic,
                '/{DATE}/' => $item->create_date,
                '/{COMMENTS}/' => number_format($item->comments),
                '/{VISITED}/' => number_format($item->visited),
                '/{DETAIL}/' => $item->description,
                '/{ICON}/' => $icon
            ));
        }
        if (isset($index->tag)) {
            // breadcrumb ของ tags
            $index->canonical = Gcms::createUrl('tag', $index->tag);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
        } elseif (isset($index->d)) {
            // breadcrumb ของ calendar
            $index->canonical = Gcms::createUrl('calendar', $index->alias);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
        } else {
            // breadcrumb ของโมดูล
            if (!empty($index->category_id) && is_array($index->category_id)) {
                // แสดงหลายหมวด
                $index->canonical = Gcms::createUrl($index->module, '', 0, 0, 'cat='.implode(',', $index->category_id));
            } else {
                // ไม่มีหมวด หรือ มีหมวดเดียว
                $index->canonical = Gcms::createUrl($index->module);
            }
            if (!Gcms::$menu->isHome($index->index_id)) {
                $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
                if ($menu) {
                    // ใช้ข้อความจากเมนู
                    Gcms::$view->addBreadcrumb($index->canonical, $menu->menu_text, $menu->menu_tooltip);
                } else {
                    // โมดูล
                    Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
                }
            } elseif (!empty($index->category_id) && is_array($index->category_id)) {
                // โมดูล
                Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
            }
        }
        if (!empty($index->category_id) && !is_array($index->category_id)) {
            // หมวดหมู่เดียว แสดงตามหมวดหมู่ที่เลือก
            $index->topic = $index->category;
            $index->description = $index->category_description;
            $index->canonical = Gcms::createUrl($index->module, '', $index->category_id);
            // หมวดหมู่
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }
        // current URL
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        // list.html หรือ empty.html ถ้าไม่มีข้อมูล
        $template = Template::create('document', $index->module, $listitem->hasItem() ? 'list' : 'empty');
        $template->add(array(
            '/{TOPIC}/' => $index->topic,
            '/{DETAIL}/' => $index->detail,
            '/{LIST}/' => $listitem->render(),
            '/{COLS}/' => $index->cols,
            '/{STYLE}/' => empty($index->style) ? 'iconview' : $index->style,
            '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
            '/{MODULE}/' => $index->module,
            '/{CATID}/' => empty($index->category_id) ? 0 : (is_array($index->category_id) ? implode(',', $index->category_id) : $index->category_id)
        ));
        // JSON-LD (Index)
        Gcms::$view->setJsonLd(\Index\Jsonld\View::webpage($index));
        // คืนค่า
        return (object) array(
            'canonical' => $index->canonical,
            'module' => $index->module,
            'topic' => $index->topic,
            'description' => $index->description,
            'keywords' => $index->keywords,
            'detail' => $template->render()
        );
    }
}
