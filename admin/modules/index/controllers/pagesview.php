<?php
/**
 * @filesource modules/index/controllers/pagesview.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Pagesview;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=pagesview
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Pagesview
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ค่าที่ส่งมา
        $date = $request->request('date', date('Y-m'))->date();
        // ข้อความ title bar
        $this->title = Language::get('Visitors report').Date::format($date.'-1', ' M Y');
        // เลือกเมนู
        $this->menu = 'home';
        // แอดมิน
        if (Login::adminAccess()) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><a class="icon-home" href="index.php">{LNG_Home}</a></li>');
            $ul->appendChild('<li><span>{LNG_Pages view}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-stats">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            $section->appendChild(\Index\Pagesview\View::create()->render($date));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
