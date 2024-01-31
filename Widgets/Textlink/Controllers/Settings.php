<?php
/**
 * @filesource Widgets/Textlink/Controllers/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlink\Controllers;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Controller สำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Gcms\Controller
{
    /**
     * แสดงผล
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        if (defined('MAIN_INIT')) {
            // ข้อความ title bar
            $this->title = Language::get('Widgets for controlling and managing the links or banners');
            // เมนู
            $this->menu = 'widgets';
            // สามารถตั้งค่าระบบได้
            if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('div', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-widgets">{LNG_Widgets}</span></li>');
                $ul->appendChild('<li><span>{LNG_Text links}</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-ads">'.$this->title().'</h2>'
                ));
                // แสดงฟอร์ม
                $section->appendChild(\Widgets\Textlink\Views\Settings::create()->render($request));
                return $section->render();
            }
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
