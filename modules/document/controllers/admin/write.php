<?php
/**
 * @filesource modules/document/controllers/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=document-write
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
        $title = empty($id) ? '{LNG_Create}' : '{LNG_Edit}';
        // ข้อความ title bar
        $this->title = Language::trans($title.' {LNG_Document}');
        // เลือกเมนู
        $this->menu = 'modules';
        // ตรวจสอบรายการที่เลือก
        $index = \Document\Admin\Write\Model::get($module_id, $id);
        // admin
        $login = Login::adminAccess();
        // can_write หรือ สมาชิกตัวอย่าง
        if ($index && $login && (Gcms::canConfig($login, $index, 'can_write') || !Login::notDemoMode($login))) {
            if (!empty($index->id)) {
                $index->details = \Document\Admin\Write\Model::details((int) $index->module_id, (int) $index->id, reset(self::$cfg->languages));
            }
            // มีการระบุหมวดมา ใช้ข้อมูลการเผยแพร่ และ ความคิดเห็นจากหมวด
            $category_id = $request->request('cat')->toInt();
            if ($category_id > 0) {
                $category = \Index\Category\Model::get($category_id, $index->module_id);
                if ($category) {
                    $index->category_id = $category->category_id;
                    $index->published = $category->published;
                    $index->can_reply = $category->can_reply;
                }
            }
            // can_reply จากโมดูลเป้นแอเรย์ แปลงเป็น int สำหรับส่งให้กับฟอร์ม
            if (is_array($index->can_reply)) {
                $index->can_reply = empty($index->can_reply) ? 0 : 1;
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
            // ภาษาที่ติดตั้ง
            $index->languages = Gcms::installedLanguage();
            // เมนูแท็บ
            $tab = new \Kotchasan\Tab('accordient_menu', 'index.php?module=document-write&mid='.$index->module_id.'&id='.$index->id);
            foreach ($index->languages as $item) {
                $tab->add('detail_'.$item, '{LNG_Detail}&nbsp;<img src='.WEB_URL.'language/'.$item.'.gif alt='.$item.'>', '', '_self');
            }
            $tab->add('options', '{LNG_Other details}', '', '_self');
            $header->appendChild($tab->render());
            if (!$index) {
                $section->appendChild('<aside class=error>{LNG_Can not be performed this request. Because they do not find the information you need or you are not allowed}</aside>');
            } else {
                $section->appendChild(\Document\Admin\Write\View::create()->render($request, $index));
            }
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
