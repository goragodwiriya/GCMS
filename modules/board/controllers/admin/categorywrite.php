<?php
/**
 * @filesource modules/board/controllers/admin/categorywrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Admin\Categorywrite;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=board-categorywrite
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข หมวดหมู่
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Category');
        // เลือกเมนู
        $this->menu = 'modules';
        // อ่านรายการที่เลือก
        $index = \Board\Admin\Categorywrite\Model::get($request->request('mid')->toInt(), $request->request('id')->toInt());
        // admin
        $login = Login::adminAccess();
        // can_config หรือ สมาชิกตัวอย่าง
        if ($index && $login && (Gcms::canConfig($login, $index, 'can_config') || !Login::notDemoMode($login))) {
            $title = Language::get(empty($index->id) ? 'Create' : 'Edit');
            $this->title = $title.' '.$this->title;
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-board">{LNG_Module}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=board-settings&mid='.$index->module_id.'}">'.ucfirst($index->module).'</a></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=board-category&mid='.$index->module_id.'}">{LNG_Category}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            $section->appendChild(\Board\Admin\Categorywrite\View::create()->render($index));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
