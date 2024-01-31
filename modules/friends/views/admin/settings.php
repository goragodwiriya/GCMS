<?php
/**
 * @filesource modules/friends/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Friends\Admin\Settings;

use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=friends-settings
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
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/friends/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Post}'
        ));
        // per_day
        $fieldset->add('select', array(
            'id' => 'per_day',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Amount}',
            'comment' => '{LNG_Limit the number of posts per day (Zero means unlimited)}',
            'options' => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10),
            'value' => $index->per_day
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Display}'
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_The number of items displayed per page}'
        ));
        // pin_per_page
        $groups->add('number', array(
            'id' => 'pin_per_page',
            'labelClass' => 'g-input icon-pin',
            'itemClass' => 'width50',
            'label' => '{LNG_Pin}',
            'value' => $index->pin_per_page
        ));
        // list_per_page
        $groups->add('number', array(
            'id' => 'list_per_page',
            'labelClass' => 'g-input icon-list',
            'itemClass' => 'width50',
            'label' => '{LNG_General}',
            'value' => $index->list_per_page
        ));
        // sex_color
        foreach (Language::get('SEXES') as $k => $v) {
            $fieldset->add('color', array(
                'id' => "sex_color_$k",
                'name' => "sex_color[$k]",
                'labelClass' => 'g-input icon-color',
                'itemClass' => 'item',
                'label' => $v,
                'value' => isset($index->sex_color[$k]) ? $index->sex_color[$k] : ''
            ));
        }
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Role of Members}'
        ));
        // สถานะสมาชิก
        $table = new HtmlTable(array(
            'class' => 'responsive horiz-table border data'
        ));
        $table->addHeader(array(
            array(),
            array('text' => '{LNG_Post}'),
            array('text' => '{LNG_Moderator}'),
            array('text' => '{LNG_Settings}')
        ));
        foreach (self::$cfg->member_status as $i => $item) {
            if ($i != 1) {
                $row = array();
                $row[] = array(
                    'scope' => 'col',
                    'text' => $item
                );
                $check = isset($index->can_post) && is_array($index->can_post) && in_array($i, $index->can_post) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => '<label data-text="{LNG_Post}"><input type=checkbox name=can_post[] title="{LNG_Members of this group can post}" value='.$i.$check.'></label>'
                );
                $check = isset($index->moderator) && is_array($index->moderator) && in_array($i, $index->moderator) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => $i > 1 ? '<label data-text="{LNG_Moderator}"><input type=checkbox name=moderator[] title="{LNG_Members of this group can edit content written by others}" value='.$i.$check.'></label>' : ''
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
