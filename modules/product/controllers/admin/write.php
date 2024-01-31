<?php
/**
 * @filesource modules/product/controllers/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Admin\Write;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=product-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข สินค้า
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
        $this->title = Language::trans($title.' {LNG_Product}');
        // เลือกเมนู
        $this->menu = 'modules';
        // ตรวจสอบรายการที่เลือก
        $index = \Product\Admin\Write\Model::get($module_id, $id, false);
        // admin
        $login = Login::adminAccess();
        // can_write หรือ สมาชิกตัวอย่าง
        if ($index && $login && (Gcms::canConfig($login, $index, 'can_write') || !Login::notDemoMode($login))) {
            if (!empty($index->id)) {
                $index->details = \Product\Admin\Write\Model::details((int) $index->module_id, (int) $index->id, reset(self::$cfg->languages));
            }
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-product">{LNG_Module}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=product-settings&mid='.$index->module_id.'}">'.ucfirst($index->module).'</a></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=product-setup&mid='.$index->module_id.'}">{LNG_List of} {LNG_Product}</a></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $header = $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            // ภาษาที่ติดตั้ง
            $index->languages = Gcms::installedLanguage();
            // เมนูแท็บ
            $tab = new \Kotchasan\Tab('accordient_menu', 'index.php?module=product-write&id='.$index->id);
            foreach ($index->languages as $item) {
                $tab->add('detail_'.$item, '{LNG_Detail}&nbsp;<img src='.WEB_URL.'language/'.$item.'.gif alt='.$item.'>');
            }
            $tab->add('options', '{LNG_Other details}');
            $header->appendChild($tab->render());
            if (!$index) {
                $section->appendChild('<aside class=error>{LNG_Can not be performed this request. Because they do not find the information you need or you are not allowed}</aside>');
            } else {
                $section->appendChild(\Product\Admin\Write\View::create()->render($request, $index));
            }
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
