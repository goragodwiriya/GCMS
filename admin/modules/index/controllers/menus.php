<?php
/**
 * @filesource modules/index/controllers/menus.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menus;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=menus
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ตารางรายการเมนู
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Create or Edit} {LNG_the menu of the site}');
        // เลือกเมนู
        $this->menu = 'index';
        // can_config
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-modules">{LNG_Menus} &amp; {LNG_Web pages}</span></li>');
            $ul->appendChild('<li><span>{LNG_Menus}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-menus">'.$this->title.'</h2>'
            ));
            // แสดงตาราง
            $section->appendChild(\Index\Menus\View::create()->render($request));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
