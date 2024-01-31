<?php
/**
 * @filesource event/controllers/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=event-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข อีเว้นต์
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ค่าที่ส่งมา
        $id = $request->request('id')->toInt();
        $module_id = $request->request('mid')->toInt();
        $title = empty($id) ? '{LNG_Create}' : '{LNG_Edit}';
        // ข้อความ title bar
        $this->title = Language::trans($title.' {LNG_Event}');
        // เลือกเมนู
        $this->menu = 'modules';
        // ตรวจสอบรายการที่เลือก
        $index = \Event\Admin\Write\Model::get($module_id, $id);
        // admin
        $login = Login::adminAccess();
        // can_write หรือ สมาชิกตัวอย่าง
        if ($index && $login && (Gcms::canConfig($login, $index, 'can_write') || !Login::notDemoMode($login))) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-event">{LNG_Module}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=event-settings&mid='.$index->module_id.'}">'.ucfirst($index->module).'</a></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=event-setup&mid='.$index->module_id.'}">{LNG_Event}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $header = $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            $section->appendChild(\Event\Admin\Write\View::create()->render($request, $index));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
