<?php
/**
 * @filesource modules/index/views/intro.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Intro;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * ฟอร์มตั้งค่าหน้า intro
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * module=intro
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
            'action' => 'index.php/index/model/intro/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Web page displayed prior to entering the home page of the website}'
        ));
        // show_intro
        $fieldset->add('select', array(
            'id' => 'show_intro',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Settings}',
            'options' => Language::get('BOOLEANS'),
            'value' => isset(self::$cfg->show_intro) ? self::$cfg->show_intro : 0
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
        $form->script('doChangeLanguage("btn_go", "index.php?module=intro");');
        return $form->render();
    }
}
