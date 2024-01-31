<?php
/**
 * @filesource modules/index/controllers/menuwrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menuwrite;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=menuwrite
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข หน้าเว็บไซต์
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // รายการที่ต้องการ
        $id = $request->request('id')->toInt();
        $title = empty($id) ? '{LNG_Create}' : '{LNG_Edit}';
        // ข้อความ title bar
        $this->title = Language::trans($title.' {LNG_Menu}');
        // เลือกเมนู
        $this->menu = 'index';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            $index = \Index\Menuwrite\Model::getMenu($request->get('_parent', 'MAINMENU')->filter('A-Z'), $id);
            if ($index) {
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('div', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-modules">{LNG_Menus} &amp; {LNG_Web pages}</span></li>');
                $ul->appendChild('<li><a href="{BACKURL?module=pages&id=0}">{LNG_Menus}</a></li>');
                $ul->appendChild('<li><span>'.$title.'</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
                ));
                // แสดงฟอร์ม
                $section->appendChild(\Index\Menuwrite\View::create()->render($request, $index));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
