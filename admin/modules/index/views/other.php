<?php
/**
 * @filesource modules/index/views/other.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Other;

use Kotchasan\Html;

/**
 * ตั้งค่าอื่นๆ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * module=other
     *
     * @param object $config
     *
     * @return string
     */
    public function render($config)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/other/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_General}'
        ));
        // member_reserv
        $fieldset->add('textarea', array(
            'id' => 'member_reserv',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Member reserve}',
            'comment' => '{LNG_Do not use these names as a member (one per line)}',
            'rows' => 6,
            'value' => empty($config->member_reserv) ? '' : implode("\n", $config->member_reserv)
        ));
        // wordrude
        $fieldset->add('textarea', array(
            'id' => 'wordrude',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Bad words}',
            'comment' => '{LNG_List of bad words (one per line)}',
            'rows' => 6,
            'value' => empty($config->wordrude) ? '' : implode("\n", $config->wordrude)
        ));
        // wordrude_replace
        $fieldset->add('text', array(
            'id' => 'wordrude_replace',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Replace}',
            'comment' => '{LNG_Bad words will be replaced with this message}',
            'value' => isset($config->wordrude_replace) ? $config->wordrude_replace : 'xxx'
        ));
        // counter_digit
        $fieldset->add('text', array(
            'id' => 'counter_digit',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Digits of the counter}',
            'comment' => '{LNG_Principal amount of the counter for preview}',
            'value' => isset($config->counter_digit) ? $config->counter_digit : self::$cfg->counter_digit
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        return $form->render();
    }
}
