<?php
/**
 * @filesource modules/documentation/controllers/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=documentation-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข เนื้อหา
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ค่าที่ส่งมา
        $id = $request->request('id')->toInt();
        $module_id = $request->request('mid')->toInt();
        $category_id = $request->request('cat')->toInt();
        $title = empty($id) ? '{LNG_Create}' : '{LNG_Edit}';
        // ข้อความ title bar
        $this->title = Language::trans($title.' {LNG_Documentation}');
        // เลือกเมนู
        $this->menu = 'modules';
        // ตรวจสอบรายการที่เลือก
        $index = \Documentation\Admin\Write\Model::get($module_id, $id, $category_id);
        // admin
        $login = Login::adminAccess();
        // can_write หรือ สมาชิกตัวอย่าง
        if ($index && $login && (Gcms::canConfig($login, $index, 'can_write') || !Login::notDemoMode($login))) {
            if (!empty($index->id)) {
                $index->details = \Documentation\Admin\Write\Model::details((int) $index->module_id, (int) $index->id, reset(self::$cfg->languages));
            }
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-documents">{LNG_Module}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=document-settings&mid='.$index->module_id.'}">'.ucfirst($index->module).'</a></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=document-setup&mid='.$index->module_id.'}">{LNG_Contents}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $header = $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            $inline = $header->add('div', array(
                'class' => 'inline'
            ));
            $writetab = $inline->add('div', array(
                'class' => 'writetab'
            ));
            $ul = $writetab->add('ul', array(
                'id' => 'accordient_menu'
            ));
            // ภาษาที่ติดตั้ง
            $index->languages = Gcms::installedLanguage();
            foreach ($index->languages as $item) {
                $ul->add('li', array(
                    'innerHTML' => '<a id=tab_detail_'.$item.' target=_self href="{BACKURL?module=documentation-write&qid='.$index->id.'&tab=detail_'.$item.'}">{LNG_Detail}&nbsp;<img src='.WEB_URL.'language/'.$item.'.gif alt='.$item.'></a>'
                ));
            }
            $ul->add('li', array(
                'innerHTML' => '<a id=tab_options target=_self href="{BACKURL?module=documentation-write&qid='.$index->id.'&tab=options}">{LNG_Other details}</a>'
            ));
            if (!$index) {
                $section->appendChild('<aside class=error>{LNG_Can not be performed this request. Because they do not find the information you need or you are not allowed}</aside>');
            } else {
                $section->appendChild(\Documentation\Admin\Write\View::create()->render($request, $index));
            }
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
