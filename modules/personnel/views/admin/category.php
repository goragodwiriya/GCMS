<?php
/**
 * @filesource modules/personnel/views/admin/category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Admin\Category;

use Gcms\Gcms;
use Kotchasan\DataTable;
use Kotchasan\Form;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=personnel-category
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ข้อมูลโมดูล
     */
    private $languages;

    /**
     * แสดงรายการหมวดหมู่
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        // ภาษาที่ติดตั้ง
        $this->languages = Gcms::installedLanguage();
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/personnel/model/admin/category/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Personnel groups}'
        ));
        // ตารางหมวดหมู่
        $table = new DataTable(array(
            /* ข้อมูลใส่ลงในตาราง */
            'datas' => \Personnel\Admin\Category\Model::all((int) $index->module_id),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* กำหนดให้ input ตัวแรก (id) รับค่าเป็นตัวเลขเท่านั้น */
            'onInitRow' => 'initFirstRowNumberOnly',
            'border' => true,
            'responsive' => true,
            'pmButton' => true,
            'showCaption' => false,
            'headers' => array(
                'category_id' => array(
                    'text' => '{LNG_ID}'
                )
            )
        ));
        $fieldset->add('div', array(
            'class' => 'item',
            'innerHTML' => $table->render()
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // module_id
        $fieldset->add('hidden', array(
            'id' => 'module_id',
            'value' => $index->module_id
        ));
        return $form->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        $item['category_id'] = Form::text(array(
            'name' => 'category_id[]',
            'labelClass' => 'g-input icon-edit',
            'size' => 2,
            'value' => $item['category_id']
        ))->render();
        foreach ($this->languages as $lng) {
            $item[$lng] = Form::text(array(
                'name' => $lng.'[]',
                'labelClass' => 'g-input',
                'value' => $item[$lng],
                'style' => 'background-image:url(../language/'.$lng.'.gif)'
            ))->render();
        }
        return $item;
    }
}
