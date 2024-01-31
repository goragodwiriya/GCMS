<?php
/**
 * @filesource modules/video/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Video\Admin\Settings;

use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;

/**
 * module=video-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * จัดการการตั้งค่า
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/video/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Settings} {LNG_YouTube}'
        ));
        // google_api_key
        $fieldset->add('text', array(
            'id' => 'google_api_key',
            'labelClass' => 'g-input icon-google',
            'itemClass' => 'item',
            'label' => '{LNG_Google API Key}',
            'comment' => '{LNG_API Key for various services from Google} <a href="http://gcms.in.th/index.php?module=howto&id=319" target="_blank" class=icon-help></a>',
            'value' => isset($index->google_api_key) ? $index->google_api_key : ''
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
            'itemClass' => 'width',
            'label' => '{LNG_Cols}',
            'options' => array(2 => 2, 4 => 4, 6 => 6, 8 => 8),
            'value' => $index->cols
        ));
        // rows
        $groups->add('select', array(
            'id' => 'rows',
            'labelClass' => 'g-input icon-rows',
            'itemClass' => 'width',
            'label' => '{LNG_Rows}',
            'options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14),
            'value' => $index->rows
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
                    'text' => $i > 1 ? '<label data-text="{LNG_Writing}"><input type=checkbox name=can_write[] title="{LNG_Members of this group can create or edit}" value='.$i.$check.'></label>' : ''
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
        return $form->render();
    }
}
