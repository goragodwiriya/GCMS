<?php
/**
 * @filesource modules/index/controllers/addmodule.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Addmodule;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=addmodule
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
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Add New} {LNG_Module}');
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
            $ul->appendChild('<li><a href="{BACKURL?module=mods&id=0}">{LNG_Installed module}</a></li>');
            $ul->appendChild('<li><span>{LNG_Create}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-new">'.$this->title.'</h2>'
            ));
            // owner
            $modules = array();
            foreach (Gcms::$module->getInstalledOwners() as $owner => $item) {
                $class = ucfirst($owner).'\Admin\Init\Controller';
                if (class_exists($class) && method_exists($class, 'description')) {
                    // get module description
                    $description = $class::description();
                    if (!empty($description)) {
                        $modules[$owner] = $description.' ['.$owner.']';
                    }
                }
            }
            // แสดงฟอร์ม
            $section->appendChild(\Index\Addmodule\View::create()->render($modules));
            // คืนค่า HTML
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
