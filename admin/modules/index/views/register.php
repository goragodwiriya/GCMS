<?php
/**
 * @filesource modules/index/views/register.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Register;

use Kotchasan\Html;

/**
 * module=register
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ลงทะเบียนสมาชิกใหม่
     *
     * @return string
     */
    public function render()
    {
        // register form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/register/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Register}'
        ));
        $groups = $fieldset->add('groups');
        if (in_array('email', self::$cfg->login_fields)) {
            // email
            $groups->add('text', array(
                'id' => 'register_email',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-email',
                'label' => '{LNG_Email}',
                'comment' => '{LNG_The system will send the registration information to this e-mail. Please use real email address}',
                'maxlength' => 255,
                'validator' => array('keyup,change', 'checkEmail', 'index.php/index/model/checker/email')
            ));
        }
        if (in_array('phone1', self::$cfg->login_fields)) {
            // phone1
            $groups->add('tel', array(
                'id' => 'register_phone1',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-phone',
                'label' => '{LNG_Phone}',
                'maxlength' => 32,
                'validator' => array('keyup,change', 'checkPhone', 'index.php/index/model/checker/phone')
            ));
        }
        $groups = $fieldset->add('groups');
        // password
        $groups->add('password', array(
            'id' => 'register_password',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-password',
            'label' => '{LNG_Password}',
            'comment' => '{LNG_Passwords must be at least four characters}',
            'maxlength' => 20,
            'validator' => array('keyup,change', 'checkPassword')
        ));
        // repassword
        $groups->add('password', array(
            'id' => 'register_repassword',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-password',
            'label' => '{LNG_Repassword}',
            'comment' => '{LNG_Enter your password again}',
            'maxlength' => 20,
            'validator' => array('keyup,change', 'checkPassword')
        ));
        // status
        $fieldset->add('select', array(
            'id' => 'register_status',
            'itemClass' => 'item',
            'label' => '{LNG_Member status}',
            'labelClass' => 'g-input icon-star0',
            'options' => self::$cfg->member_status,
            'value' => 0
        ));
        $fieldset->add('checkboxgroups', array(
            'id' => 'register_permission',
            'label' => '{LNG_Permission}',
            'labelClass' => 'g-input icon-list',
            'options' => \Gcms\Controller::getPermissions(),
            'value' => array()
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Register}'
        ));
        $fieldset->add('hidden', array(
            'id' => 'register_id',
            'value' => 0
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
