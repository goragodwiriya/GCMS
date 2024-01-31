<?php
/**
 * @filesource modules/friends/views/lists.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Friends\Lists;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Province;
use Kotchasan\Template;

/**
 * แสดงรายการข้อความ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงรายการข้อความ
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function index(Request $request, $index)
    {
        // ค่าที่ส่งมา
        $index->province_id = $request->request('province')->toInt();
        $index->sex = $request->request('sex')->filter('a-z');
        // ลิสต์ข้อมูล
        $index = \Friends\Lists\Model::get($request, $index);
        if ($index) {
            // รายชื่อจังหวัด
            $provinces = array();
            foreach (Province::all() as $iso => $name) {
                $provinces[] = '<option value="'.$iso.'">'.$name.'</option>';
            }
            $provinces = implode('', $provinces);
            // เพศ
            $sexes = Language::get('SEXES');
            // ข้อมูลคน Login
            $login = Login::isMember();
            if (empty($index->pins) && empty($index->items)) {
                // empty.html
                $pins = Template::load('friends', $index->module, 'empty');
                $listitem = '';
            } else {
                // ผู้ดูแล
                $moderator = $login && in_array($login['status'], $index->moderator);
                // listitem.html
                $pins = Grid::create('friends', $index->module, 'listitem');
                foreach ($index->pins as $item) {
                    $pins->add(array(
                        '/(delete-{QID}-0-0-{MODULEID})/' => $moderator ? '\\1' : 'hidden',
                        '/(pin-{QID}-0-0-{MODULEID})/' => $moderator ? '\\1' : 'hidden',
                        '/{QID}/' => $item->id,
                        '/{TOPIC}/' => Gcms::checkRude($item->topic),
                        '/{UID}/' => $item->member_id,
                        '/{COLOR}/' => isset($index->sex_color[$item->sex]) ? $index->sex_color[$item->sex] : 'inherit',
                        '/{SENDER}/' => $item->sender,
                        '/{STATUS}/' => $item->status,
                        '/{DATE}/' => Date::format($item->create_date, 'd M Y'),
                        '/{PROVINCE}/' => Province::get($item->province_id),
                        '/{GENDER}/' => isset($sexes[$item->sex]) ? $sexes[$item->sex] : '',
                        '/{PIN_TITLE}/' => '{LNG_Unpin}',
                        '/{PIN}/' => ''
                    ));
                }
                $pins = $pins->hasItem() ? $pins->render() : '';
                // listitem.html
                $listitem = Grid::create('friends', $index->module, 'listitem');
                foreach ($index->items as $item) {
                    $listitem->add(array(
                        '/(delete-{QID}-0-0-{MODULEID})/' => $moderator ? '\\1' : 'hidden',
                        '/(pin-{QID}-0-0-{MODULEID})/' => $moderator ? '\\1' : 'hidden',
                        '/{QID}/' => $item->id,
                        '/{TOPIC}/' => Gcms::checkRude($item->topic),
                        '/{UID}/' => $item->member_id,
                        '/{COLOR}/' => isset($index->sex_color[$item->sex]) ? $index->sex_color[$item->sex] : 'inherit',
                        '/{SENDER}/' => $item->sender,
                        '/{STATUS}/' => $item->status,
                        '/{DATE}/' => Date::format($item->create_date, 'd M Y'),
                        '/{PROVINCE}/' => Province::get($item->province_id),
                        '/{GENDER}/' => isset($sexes[$item->sex]) ? $sexes[$item->sex] : '',
                        '/{PIN_TITLE}/' => '{LNG_Pin}',
                        '/{PIN}/' => 'un'
                    ));
                }
                $listitem = $listitem->hasItem() ? $listitem->render() : '';
            }
            // URL ของหน้านี้
            $index->canonical = Gcms::createUrl($index->module);
            // current URL
            $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
            // มีการระบุจังหวัดมา
            if ($index->province_id > 0) {
                $uri = $uri->withParams(array('province' => $index->province_id));
            }
            $tabs = array('<a class="'.($index->sex == '' ? 'select' : '').'" href="'.$uri.'">{LNG_all items}</a>');
            foreach ($sexes as $k => $v) {
                $c = isset($index->sex_color[$k]) ? ' style="background-color:'.$index->sex_color[$k].'" ' : ' ';
                $tabs[] = '<a class="'.($index->sex == $k ? 'select' : '').'"'.$c.'href="'.$uri->withParams(array('sex' => $k)).'">'.$v.'</a>';
            }
            // เพิ่มรายการ ทั้งหมด
            $province_menu = '<option value="">'.Language::get('all items').'</option>'.$provinces;
            // มีการระบุเพศมา
            if ($index->sex != '') {
                $uri = $uri->withParams(array('sex' => $index->sex));
            }
            // /friends/list.html
            $template = Template::create('friends', $index->module, 'list');
            $template->add(array(
                '/{TOPIC}/' => $index->topic,
                '/{DETAIL}/' => $index->detail,
                '/{PIN}/' => $pins,
                '/{LIST}/' => $listitem,
                '/{SPLITPAGE}/' => $uri->pagination($index->totalpage, $index->page),
                '/{NEWTOPIC}/' => empty($index->can_post) ? 'hidden' : '',
                '/{MODULE}/' => $index->module,
                '/{PROVINCE}/' => $login ? str_replace('value="'.$login['provinceID'].'"', 'value="'.$login['provinceID'].'" selected', $provinces) : $provinces,
                '/{PROVINCE_MENU}/' => $index->province_id > 0 ? str_replace('value="'.$index->province_id.'"', 'value="'.$index->province_id.'" selected', $province_menu) : $province_menu,
                '/{TABS}/' => implode('', $tabs),
                '/{MODULEID}/' => $index->module_id,
                '/{TOKEN}/' => $request->createToken()
            ));
            // ข้อมูลใส่หลังจาก render แล้ว
            Gcms::$view->setContentsAfter(array(
                '/%COUNT%/' => $index->per_day
            ));
            // breadcrumb ของโมดูล
            if (Gcms::$menu->isHome($index->index_id)) {
                $index->canonical = WEB_URL.'index.php';
            } else {
                $menu = Gcms::$menu->findTopLevelMenu($index->index_id);
                if ($menu) {
                    Gcms::$view->addBreadcrumb($index->canonical, $menu->menu_text, $menu->menu_tooltip);
                }
            }
            // คืนค่า
            $index->detail = $template->render();
            return $index;
        }
    }
}
