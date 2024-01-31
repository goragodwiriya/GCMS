<?php
/**
 * @filesource modules/index/controllers/debug.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Debug;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=debug
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Debug
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Debug tool');
        // เลือกเมนู
        $this->menu = 'tools';
        // สามารถตั้งค่าระบบได้
        if (Login::notDemoMode(Login::isAdmin())) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-tools">{LNG_Tools}</span></li>');
            $ul->appendChild('<li><span>'.$this->title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-world">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'setup_frm'
            ));
            $div = $div->add('div', array(
                'class' => 'item'
            ));
            $div->appendChild('<div id="debug_layer"></div>');
            $div->appendChild('<div class="submit right"><a id="debug_clear" class="button large red">{LNG_Clear}</a></div>');
            $section->script('showDebug();');
            // คืนค่า HTML
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
