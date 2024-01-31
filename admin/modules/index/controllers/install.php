<?php
/**
 * @filesource modules/index/controllers/install.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Install;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=install
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * เพิ่มโมดูลแบบที่สามารถใช้ซ้ำได้
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // โมดูลที่ต้องการติดตั้ง
        $module = $request->request('m')->filter('a-z');
        $widget = $request->request('w')->filter('a-z');
        $module = $module !== '' ? $module : $widget;
        // ข้อความ title bar
        $this->title = Language::trans(ucfirst($module).' - {LNG_First Install}');
        // เลือกเมนู
        $this->menu = 'tools';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array('class' => 'breadcrumbs'));
            $ul = $breadcrumbs->add('ul');
            if ($module !== '') {
                $ul->appendChild('<li><span class="icon-modules">{LNG_Module}</span></li>');
                $type = 'module';
            } elseif ($widget !== '') {
                $ul->appendChild('<li><span class="icon-widgets">{LNG_Widgets}</span></li>');
                $type = 'widget';
            }
            if (!empty($type)) {
                $ul->appendChild('<li><span>{LNG_Install}</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-inbox">'.$this->title.'</h2>'
                ));
                // แสดงฟอร์ม
                $section->appendChild(\Index\Install\View::create()->render($type, $module));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
