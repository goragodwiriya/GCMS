<?php
/**
 * @filesource modules/index/controllers/mods.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mods;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=mods
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายการโมดูลที่ติดตั้งแล้ว
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('List all installed modules available');
        // เลือกเมนู
        $this->menu = 'index';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-modules">{LNG_Menus} &amp; {LNG_Web pages}</span></li>');
            $ul->appendChild('<li><span>{LNG_Installed module}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-modules">'.$this->title.'</h2>'
            ));
            // แสดงตาราง
            $section->appendChild(\Index\Mods\View::create()->render($request));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
