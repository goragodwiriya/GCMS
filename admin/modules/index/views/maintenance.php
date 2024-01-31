<?php
/**
 * @filesource modules/index/views/maintenance.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Maintenance;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * ตั้งค่าหน้าพักเว็บไซต์ชั่วคราว (maintenance)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * module=maintenance
     *
     * @param string $language
     * @param string $template
     *
     * @return string
     */
    public function render($language, $template)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/maintenance/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_The page will appear on your site is in maintenance mode}'
        ));
        // maintenance_mode
        $fieldset->add('select', array(
            'id' => 'maintenance_mode',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Settings}',
            'options' => Language::get('BOOLEANS'),
            'value' => isset(self::$cfg->maintenance_mode) ? self::$cfg->maintenance_mode : 0
        ));
        $div = $fieldset->add('groups-table', array(
            'label' => '{LNG_Language}'
        ));
        // language
        $div->add('select', array(
            'id' => 'language',
            'labelClass' => 'g-input icon-language',
            'itemClass' => 'width',
            'options' => Language::installedLanguage(),
            'value' => $language
        ));
        $div->add('button', array(
            'id' => 'btn_go',
            'itemClass' => 'width',
            'class' => 'button go',
            'value' => '{LNG_Go}'
        ));
        // detail
        $fieldset->add('ckeditor', array(
            'id' => 'detail',
            'itemClass' => 'item',
            'height' => 300,
            'language' => Language::name(),
            'toolbar' => 'Document',
            'label' => '{LNG_Detail}',
            'value' => $template,
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
        $form->script('doChangeLanguage("btn_go", "index.php?module=maintenance");');
        return $form->render();
    }
}
