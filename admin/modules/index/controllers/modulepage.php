<?php
/**
 * @filesource modules/index/controllers/modulepage.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Modulepage;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=modulepage
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข topic, descript ของหน้าเว็บไซต์ย่อยของโมดูล
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Module page');
        // เลือกเมนู
        $this->menu = 'index';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // รายการที่ต้องการ
            $index = \Index\Modulepage\Model::get($request->request('id')->toInt());
            if ($index) {
                $title = Language::get(empty($index->id) ? 'Create' : 'Edit');
                $this->title = $title.' '.$this->title;
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('div', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-modules">{LNG_Menus} &amp; {LNG_Web pages}</span></li>');
                $ul->appendChild('<li><a href="{BACKURL?module=modulepages&id=0}">{LNG_Module page}</a></li>');
                $ul->appendChild('<li><span>'.$title.'</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
                ));
                // แสดงฟอร์ม
                $section->appendChild(\Index\Modulepage\View::create()->render($index));
                return $section->render();
            }
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
