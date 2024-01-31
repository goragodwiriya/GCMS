<?php
/**
 * @filesource modules/index/controllers/pagewrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Pagewrite;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=pagewrite
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข โมดูล
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Module');
        // เลือกเมนู
        $this->menu = 'index';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // รายการที่ต้องการ
            $index = \Index\Pagewrite\Model::getIndex($request->request('id')->toInt(), $request->request('owner', 'index')->topic());
            if ($index) {
                $title = Language::get(empty($index->id) ? 'Create' : 'Edit');
                $this->title = $title.' '.$this->title.' ('.$index->owner.')';
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('div', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-modules">{LNG_Menus} &amp; {LNG_Web pages}</span></li>');
                $ul->appendChild('<li><a href="{BACKURL?module=pages&id=0}">{LNG_'.($index->owner == 'index' ? 'Web pages' : 'Installed module').'}</a></li>');
                $ul->appendChild('<li><span>'.$title.'</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
                ));
                // แสดงฟอร์ม
                $section->appendChild(\Index\Pagewrite\View::create()->render($index));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
