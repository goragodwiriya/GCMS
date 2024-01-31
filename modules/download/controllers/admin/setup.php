<?php
/**
 * @filesource modules/download/controllers/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Admin\Setup;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=download-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แสดงรายการดาวน์โหลด
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_List of} {LNG_Download file}');
        // เลือกเมนู
        $this->menu = 'modules';
        // อ่านข้อมูลโมดูล และ config
        $index = \Index\Adminmodule\Model::getModuleWithConfig('download', $request->request('mid')->toInt());
        // admin
        $login = Login::adminAccess();
        // can_upload หรือ ผู้ดูแล หรือ สมาชิกตัวอย่าง
        if ($index && $login && (Gcms::canConfig($login, $index, array('can_upload', 'moderator')) || !Login::notDemoMode($login))) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-download">{LNG_Module}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=download-settings&mid='.$index->module_id.'}">'.ucfirst($index->module).'</a></li>');
            $ul->appendChild('<li><span>{LNG_List of} {LNG_Download file}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'
            ));
            // แสดงตาราง
            $section->appendChild(\Download\Admin\Setup\View::create()->render($request, $index, $login));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
