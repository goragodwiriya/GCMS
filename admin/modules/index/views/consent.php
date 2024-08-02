<?php
/**
 * @filesource modules/index/views/consent.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Consent;

use Kotchasan\Html;

/**
 * module=consent
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * นโยบายคุกกี้
     *
     * @param object $config
     *
     * @return string
     */
    public function render($config)
    {
        $form = Html::create('form', [
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/consent/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_General}'
        ]);
        // data_controller
        $fieldset->add('email', [
            'id' => 'data_controller',
            'labelClass' => 'g-input icon-email',
            'itemClass' => 'item',
            'label' => '{LNG_Data controller}',
            'comment' => '{LNG_The e-mail address of the person or entity that has the authority to make decisions about the collection, use or dissemination of personal data.}',
            'value' => isset($config->data_controller) ? $config->data_controller : ''
        ]);

        // privacy_module
        $fieldset->add('select', [
            'id' => 'privacy_module',
            'labelClass' => 'g-input icon-modules',
            'itemClass' => 'item',
            'label' => '{LNG_Privacy Policy}',
            'comment' => '{LNG_Choose from a webpage of already created}',
            'options' => ['' => '{LNG_Not specified}']+\Index\Pages\Model::toSelect(),
            'value' => isset($config->privacy_module) ? $config->privacy_module : ''
        ]);
        $fieldset = $form->add('fieldset', [
            'class' => 'submit'
        ]);
        // submit
        $fieldset->add('submit', [
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ]);
        // คืนค่า HTML
        return $form->render();
    }
}
