<?php
/**
 * @filesource modules/index/controllers/register.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Register;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=register
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ลงทะเบียนสมาชิกใหม่
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Create new account');
        // เลือกเมนู
        $this->menu = 'users';
        // แอดมิน, ไม่ใช่สมาชิกตัวอย่าง
        if ($login = Login::notDemoMode(Login::isAdmin())) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><a class="icon-user" href="index.php?module=member">{LNG_Users}</a></li>');
            $ul->appendChild('<li><span>{LNG_Register}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-register">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            $section->appendChild(\Index\Register\View::create()->render());
            // คืนค่า HTML
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
