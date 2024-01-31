<?php
/**
 * @filesource modules/product/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Admin\Settings;

use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=product-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * จัดการการตั้งค่าโมดูล
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/product/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Product}'
        ));
        // product_no
        $fieldset->add('text', array(
            'id' => 'product_no',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Product Code}',
            'comment' => '{LNG_number format such as %04d (%04d means the number on 4 digits, up to 11 digits)}',
            'placeholder' => '%04d',
            'value' => isset($index->product_no) ? $index->product_no : ''
        ));
        // currency_unit
        $fieldset->add('select', array(
            'id' => 'currency_unit',
            'labelClass' => 'g-input icon-currency',
            'itemClass' => 'item',
            'label' => '{LNG_Currency Unit}',
            'comment' => '{LNG_Currency for goods and services}',
            'options' => Language::get('CURRENCY_UNITS'),
            'value' => isset($index->currency_unit) ? $index->currency_unit : 'THB'
        ));
        $groups = $fieldset->add('groups');
        // thumb_width
        $groups->add('number', array(
            'id' => 'thumb_width',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Size of} {LNG_Thumbnail}',
            'placeholder' => '696 {LNG_Pixel}',
            'comment' => '{LNG_Images shown in the catalog of products}',
            'value' => $index->thumb_width
        ));
        // image_width
        $groups->add('number', array(
            'id' => 'image_width',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Size of} {LNG_Image}',
            'placeholder' => '800 {LNG_Pixel}',
            'comment' => '{LNG_Pictures displayed at the product details page}',
            'value' => $index->image_width
        ));
        // img_typies
        $fieldset->add('checkboxgroups', array(
            'id' => 'img_typies',
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Types of files that can be uploaded} ({LNG_must choose at least one item})',
            'labelClass' => 'g-input icon-thumbnail',
            'options' => array('jpg' => 'jpg', 'jpeg' => 'jpeg', 'gif' => 'gif', 'png' => 'png'),
            'value' => $index->img_typies
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Display}'
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_The number of items displayed per page}'
        ));
        // cols
        $groups->add('select', array(
            'id' => 'cols',
            'labelClass' => 'g-input icon-cols',
            'itemClass' => 'width50',
            'label' => '{LNG_Cols}',
            'options' => array(1 => 1, 2 => 2, 4 => 4, 6 => 6, 8 => 8),
            'value' => $index->cols
        ));
        // rows
        $groups->add('number', array(
            'id' => 'rows',
            'labelClass' => 'g-input icon-rows',
            'itemClass' => 'width50',
            'label' => '{LNG_Rows}',
            'value' => $index->rows
        ));
        // sort
        $sorts = array('{LNG_Product Code} 9-0', '{LNG_Product Code} 0-9', '{LNG_Last updated}', '{LNG_Random}');
        $fieldset->add('select', array(
            'id' => 'sort',
            'labelClass' => 'g-input icon-sort',
            'itemClass' => 'item',
            'label' => '{LNG_Sort}',
            'comment' => '{LNG_Determine how to sort the items displayed}',
            'options' => $sorts,
            'value' => $index->sort
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Role of Members}'
        ));
        // สถานะสมาชิก
        $table = new HtmlTable(array(
            'class' => 'responsive horiz-table border data'
        ));
        $table->addHeader(array(
            array(),
            array('text' => '{LNG_Writing}'),
            array('text' => '{LNG_Settings}')
        ));
        foreach (self::$cfg->member_status as $i => $item) {
            if ($i > 1) {
                $row = array();
                $row[] = array(
                    'scope' => 'col',
                    'text' => $item
                );
                $check = isset($index->can_write) && is_array($index->can_write) && in_array($i, $index->can_write) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => $i > 0 ? '<label data-text="{LNG_Writing}"><input type=checkbox name=can_write[] title="{LNG_Members of this group can create or edit}" value='.$i.$check.'></label>' : ''
                );
                $check = isset($index->can_config) && is_array($index->can_config) && in_array($i, $index->can_config) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => $i > 1 ? '<label data-text="{LNG_Settings}"><input type=checkbox name=can_config[] title="{LNG_Members of this group can setting the module (not recommend)}" value='.$i.$check.'></label>' : ''
                );
                $table->addRow($row, array(
                    'class' => 'status'.$i
                ));
            }
        }
        $div = $fieldset->add('div', array(
            'class' => 'item'
        ));
        $div->appendChild($table->render());
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // id
        $fieldset->add('hidden', array(
            'name' => 'id',
            'value' => $index->module_id
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
