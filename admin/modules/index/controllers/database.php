<?php
/**
 * @filesource modules/index/controllers/database.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Database;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=database
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Database
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Backup and restore database');
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
            $ul->appendChild('<li><span>{LNG_Database}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-database">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'setup_frm'
            ));
            // แสดงฟอร์ม
            $view = new \Index\Database\View();
            $div->appendChild($view->export());
            $div->appendChild($view->import());
            // คืนค่า HTML
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
