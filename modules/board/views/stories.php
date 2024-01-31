<?php
/**
 * @filesource modules/board/views/stories.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Stories;

use Board\Index\Controller;
use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * แสดงรายการกระทู้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายการกระทู้
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ลิสต์รายการ
        $index = \Board\Stories\Model::get($request, $index);
        if ($index) {
            // login
            $login = $request->session('login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''))->all();
            // วันที่สำหรับเครื่องหมาย new
            $valid_date = time() - $index->new_date;
            // /board/listitem.html
            $listitem = Template::create('board', $index->module, 'listitem');
            foreach ($index->items as $item) {
                if (!empty($item->picture) && is_file(ROOT_PATH.DATA_FOLDER.'board/thumb-'.$item->picture)) {
                    $thumb = WEB_URL.DATA_FOLDER.'board/thumb-'.$item->picture;
                } elseif ($item->pin > 0) {
                    $thumb = WEB_URL.'skin/'.self::$cfg->skin.'/board/img/pin.png';
                } elseif ($item->locked > 0) {
                    $thumb = WEB_URL.'skin/'.self::$cfg->skin.'/board/img/lock.png';
                } elseif (!empty($index->icon) && is_file(ROOT_PATH.DATA_FOLDER.'board/'.$index->icon)) {
                    $thumb = WEB_URL.DATA_FOLDER.'board/'.$index->icon;
                } else {
                    $thumb = WEB_URL.(isset($index->default_icon) ? $index->default_icon : 'modules/board/img/default_icon.png');
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
                    '/{URL}/' => Controller::url($index->module, $item->id),
                    '/{TOPIC}/' => $item->topic,
                    '/{UID}/' => $item->member_id,
                    '/{SENDER}/' => $item->sender,
                    '/{STATUS}/' => $item->status,
                    '/{DATE}/' => $item->create_date,
                    '/{VISITED}/' => number_format($item->visited),
                    '/{REPLY}/' => number_format($item->comments),
                    '/{REPLYDATE}/' => $item->comment_date == 0 ? '' : $item->comment_date,
                    '/{REPLYER}/' => $item->comment_date == 0 ? '&nbsp;' : $item->commentator,
                    '/{STATUS2}/' => $item->replyer_status,
                    '/{RID}/' => $item->commentator_id,
                    '/{ICON}/' => $icon
                ));
            }
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
            } elseif (!empty($index->category_id)) {
                // โมดูล
                Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
            }
            if (empty($index->category_id)) {
                // ไม่มีหมวด
                $category_id = 0;
            } elseif (is_array($index->category_id)) {
                // แสดงหลายหมวด
                $category_id = 0;
            } else {
                // หมวดหมู่เดียว แสดงตามหมวดหมู่ที่เลือก
                $category_id = $index->category_id;
                $index->topic = $index->category;
                $index->canonical = Gcms::createUrl($index->module, '', $category_id);
                // หมวดหมู่
                Gcms::$view->addBreadcrumb($index->canonical, $index->category);
            }
            // current URL
            $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
            // list.html หรือ empty.html หากไม่มีข้อมูล
            $template = Template::create('board', $index->module, $listitem->hasItem() ? 'list' : 'empty');
            $template->add(array(
                '/{TOPIC}/' => $index->topic,
                '/{DETAIL}/' => $index->detail,
                '/{LIST}/' => $listitem->render(),
                '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
                '/{NEWTOPIC}/' => empty($index->can_post) ? 'hidden' : '',
                '/{CATID}/' => $category_id,
                '/{MODULE}/' => $index->module
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
        // 404
        return createClass('Index\Error\Controller')->init('board');
    }
}
