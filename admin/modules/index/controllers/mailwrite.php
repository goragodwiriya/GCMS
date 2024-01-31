<?php
/**
 * @filesource modules/index/controllers/mailwrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mailwrite;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=mailwrite
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มเขียน/แก้ไข แม่แบบอีเมล
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // รายการที่ต้องการ
        $index = \Index\Mailwrite\Model::getIndex($request->request('id')->toInt());
        // ข้อความ title bar
        $title = empty($index->id) ? '{LNG_Create}' : '{LNG_Edit}';
        // ข้อความ title bar
        $this->title = Language::trans($title.' {LNG_Email template}');
        // เลือกเมนู
        $this->menu = 'settings';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-settings">{LNG_Site settings}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=mailtemplate&id=0}">{LNG_Email template}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.' '.$index->name.'</h2>'
            ));
            if ($index) {
                // แสดงฟอร์ม
                $section->appendChild(\Index\Mailwrite\View::create()->render($index));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
