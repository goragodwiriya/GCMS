<?php
/**
 * @filesource modules/index/controllers/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Report;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Report
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ค่าที่ส่งมา
        $params = array(
            'ip' => $request->request('ip')->filter('0-9\.'),
            'date' => $request->request('date', date('Y-m-d'))->date(),
            'h' => $request->request('h', -1)->toInt()
        );
        // ข้อความ title bar
        $this->title = Language::get('Visitors report').Date::format($params['date'], ' d M Y').(empty($params['ip']) ? '' : ' IP '.$params['ip']);
        // เลือกเมนู
        $this->menu = 'home';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-home">{LNG_Home}</span></li>');
            $ul->appendChild('<li><a href="index.php?module=pagesview">{LNG_Pages view}</a></li>');
            $ul->appendChild('<li><a href="index.php?module=report&amp;date='.$params['date'].'">{LNG_Report}</a></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-stats">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            $section->appendChild(\Index\Report\View::create()->render($request, $params));
            // คืนค่า HTML
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
