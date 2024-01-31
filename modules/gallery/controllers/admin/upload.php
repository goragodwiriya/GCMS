<?php
/**
 * @filesource modules/gallery/controllers/admin/upload.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Upload;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=gallery-upload
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มอัปโหลด
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Upload your photos into} {LNG_Album}');
        // เลือกเมนู
        $this->menu = 'modules';
        // ตรวจสอบรายการที่เลือก
        $index = \Gallery\Admin\Write\Model::get($request->request('mid')->toInt(), $request->request('id')->toInt());
        // admin
        $login = Login::adminAccess();
        // can_write หรือ สมาชิกตัวอย่าง
        if ($index && $login && (Gcms::canConfig($login, $index, 'can_write') || !Login::notDemoMode($login))) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-gallery">{LNG_Module}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=gallery-settings&mid='.$index->module_id.'}">'.ucfirst($index->module).'</a></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=gallery-setup&mid='.$index->module_id.'}">{LNG_Album}</a></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=gallery-write&id='.$index->id.'}">'.$index->topic.'</a></li>');
            $ul->appendChild('<li><span>{LNG_Upload}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            $section->appendChild(\Gallery\Admin\Upload\View::create()->render($request, $index));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
