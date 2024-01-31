<?php
/**
 * @filesource Widgets/Marquee/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Marquee\Views;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Gcms\Adminview
{
    /**
     * module=Marquee-Settings
     *
     * @return string
     */
    public function render()
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/Widgets/Marquee/Models/Settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Configuring}'
        ));
        // marquee_speed
        $fieldset->add('number', array(
            'id' => 'marquee_speed',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Speed}',
            'comment' => '{LNG_The speed of the text runs from 1 to 100 (Number one fastest)}',
            'value' => empty(self::$cfg->marquee['speed']) ? 20 : self::$cfg->marquee['speed']
        ));
        // marquee_style
        $fieldset->add('select', array(
            'id' => 'marquee_style',
            'labelClass' => 'g-input icon-drag',
            'itemClass' => 'item',
            'label' => '{LNG_Style}',
            'options' => array('left' => 'Right to Left', 'right' => 'Left to Right', 'bottom' => 'Top to Bottom', 'top' => 'Bottom to Top'),
            'value' => empty(self::$cfg->marquee['style']) ? 'left' : self::$cfg->marquee['style']
        ));
        // marquee_text
        $fieldset->add('ckeditor', array(
            'id' => 'marquee_text',
            'itemClass' => 'item',
            'height' => 300,
            'language' => Language::name(),
            'toolbar' => 'Document',
            'label' => '{LNG_Detail}',
            'value' => empty(self::$cfg->marquee['text']) ? '' : self::$cfg->marquee['text'],
            'upload' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
