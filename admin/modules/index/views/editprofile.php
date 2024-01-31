<?php
/**
 * @filesource modules/index/views/editprofile.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Editprofile;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=editprofile
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มแก้ไขสมาชิก
     *
     * @param array $user
     * @param array $login
     *
     * @return string
     */
    public function render($user, $login)
    {
        $login_admin = Login::isAdmin();
        // register form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/editprofile/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Login information}'
        ));
        $groups = $fieldset->add('groups');
        if (in_array('email', self::$cfg->login_fields)) {
            // email (แอดมิน และตัวเอง สามารถแก้ไขได้)
            $groups->add('text', array(
                'id' => 'register_email',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-email',
                'label' => '{LNG_Email}',
                'comment' => '{LNG_The system will send the registration information to this e-mail. Please use real email address}',
                'disabled' => $login_admin ? false : true,
                'maxlength' => 255,
                'value' => $user['email'],
                'validator' => array('keyup,change', 'checkEmail', 'index.php/index/model/checker/email')
            ));
        }
        if (in_array('phone1', self::$cfg->login_fields)) {
            // phone1 (แอดมิน และตัวเอง สามารถแก้ไขได้)
            $groups->add('tel', array(
                'id' => 'register_phone1',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-phone',
                'label' => '{LNG_Phone}',
                'disabled' => $login_admin ? false : true,
                'maxlength' => 32,
                'value' => $user['phone1'],
                'validator' => array('keyup,change', 'checkPhone', 'index.php/index/model/checker/phone')
            ));
        }
        // password, repassword
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_To change your password, enter your password to match the two inputs}'
        ));
        // password
        $groups->add('password', array(
            'id' => 'register_password',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-password',
            'label' => '{LNG_Password}',
            'placeholder' => '{LNG_Passwords must be at least four characters}',
            'maxlength' => 20,
            'validator' => array('keyup,change', 'checkPassword')
        ));
        // repassword
        $groups->add('password', array(
            'id' => 'register_repassword',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-password',
            'label' => '{LNG_Repassword}',
            'placeholder' => '{LNG_Enter your password again}',
            'maxlength' => 20,
            'validator' => array('keyup,change', 'checkPassword')
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Other} ({LNG_Public})'
        ));
        $groups = $fieldset->add('groups');
        // displayname
        $groups->add('text', array(
            'id' => 'register_displayname',
            'labelClass' => 'g-input icon-user',
            'itemClass' => 'width50',
            'label' => '{LNG_Displayname}',
            'comment' => '{LNG_Name for the show on the site at least 2 characters}',
            'maxlength' => 50,
            'value' => $user['displayname'],
            'validator' => array('keyup,change', 'checkDisplayname', 'index.php/index/model/checker/displayname')
        ));
        // sex
        $groups->add('select', array(
            'id' => 'register_sex',
            'labelClass' => 'g-input icon-sex',
            'itemClass' => 'width50',
            'label' => '{LNG_Sex}',
            'options' => Language::get('SEXES'),
            'value' => $user['sex']
        ));
        // icon
        if (is_file(ROOT_PATH.self::$cfg->usericon_folder.$user['icon'])) {
            $icon = WEB_URL.self::$cfg->usericon_folder.$user['icon'];
        } else {
            $icon = WEB_URL.'skin/img/noicon.jpg';
        }
        $fieldset->add('file', array(
            'id' => 'icon',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Avatar}',
            'comment' => Language::replace('Upload a picture of :type', array(':type' => implode(', ', self::$cfg->user_icon_typies))).' ({LNG_automatic resize})',
            'dataPreview' => 'imgPicture',
            'previewSrc' => $icon,
            'accept' => self::$cfg->user_icon_typies
        ));
        // website
        $fieldset->add('url', array(
            'id' => 'register_website',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'item',
            'label' => '{LNG_Website}',
            'maxlength' => 255,
            'value' => $user['website'],
            'comment' => '{LNG_Your site&#039;s URL (include http://)}'
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Profile}'
        ));
        $groups = $fieldset->add('groups');
        // name
        $groups->add('text', array(
            'id' => 'register_name',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'width50',
            'label' => '{LNG_Name}',
            'maxlength' => 150,
            'value' => $user['name']
        ));
        // idcard
        $groups->add('number', array(
            'id' => 'register_idcard',
            'labelClass' => 'g-input icon-profile',
            'itemClass' => 'width50',
            'label' => '{LNG_Identification No.}',
            'placeholder' => '{LNG_13-digit identification number}',
            'maxlength' => 13,
            'value' => $user['idcard'],
            'validator' => array('keyup,change', 'checkIdcard', 'index.php/index/model/checker/idcard')
        ));
        $groups = $fieldset->add('groups');
        // birthday
        $groups->add('date', array(
            'id' => 'register_birthday',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width50',
            'label' => '{LNG_Birthday}',
            'value' => $user['birthday']
        ));
        // company
        $groups->add('text', array(
            'id' => 'register_company',
            'labelClass' => 'g-input icon-address',
            'itemClass' => 'width50',
            'label' => '{LNG_Company}',
            'placeholder' => '{LNG_Your workplace}',
            'maxlength' => 255,
            'value' => $user['company']
        ));
        $groups = $fieldset->add('groups');
        if (!in_array('phone1', self::$cfg->login_fields)) {
            // phone1
            $groups->add('tel', array(
                'id' => 'register_phone1',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-phone',
                'label' => '{LNG_Phone}',
                'disabled' => $login_admin ? false : true,
                'maxlength' => 32,
                'value' => $user['phone1'],
                'validator' => array('keyup,change', 'checkPhone', 'index.php/index/model/checker/phone')
            ));
        }
        if (!in_array('email', self::$cfg->login_fields)) {
            // email (แอดมิน และตัวเอง สามารถแก้ไขได้)
            $groups->add('text', array(
                'id' => 'register_email',
                'itemClass' => 'width50',
                'labelClass' => 'g-input icon-email',
                'label' => '{LNG_Email}',
                'comment' => '{LNG_The system will send the registration information to this e-mail. Please use real email address}',
                'disabled' => $login_admin ? false : true,
                'maxlength' => 255,
                'value' => $user['email'],
                'validator' => array('keyup,change', 'checkEmail', 'index.php/index/model/checker/email')
            ));
        }
        // phone2
        $groups->add('tel', array(
            'id' => 'register_phone2',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width50',
            'label' => '{LNG_Phone}/{LNG_Fax}',
            'maxlength' => 32,
            'value' => $user['phone2']
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Address details}'
        ));
        // address1
        $fieldset->add('text', array(
            'id' => 'register_address1',
            'labelClass' => 'g-input icon-address',
            'itemClass' => 'item',
            'label' => '{LNG_Address} 1',
            'maxlength' => 64,
            'value' => $user['address1']
        ));
        // address2
        $fieldset->add('text', array(
            'id' => 'register_address2',
            'labelClass' => 'g-input icon-address',
            'itemClass' => 'item',
            'label' => '{LNG_Address} 2',
            'maxlength' => 64,
            'value' => $user['address2']
        ));
        $groups = $fieldset->add('groups');
        // province
        $groups->add('text', array(
            'id' => 'register_province',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width33',
            'label' => '{LNG_Province}',
            'value' => $user['province']
        ));
        // provinceID
        $groups->add('select', array(
            'id' => 'register_provinceID',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width33',
            'label' => '{LNG_Province}',
            'options' => \Kotchasan\Province::all(),
            'value' => $user['provinceID']
        ));
        // zipcode
        $groups->add('number', array(
            'id' => 'register_zipcode',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width33',
            'label' => '{LNG_Zipcode}',
            'maxlength' => 5,
            'value' => $user['zipcode']
        ));
        // country
        $groups->add('select', array(
            'id' => 'register_country',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width33',
            'label' => '{LNG_Country}',
            'options' => \Kotchasan\Country::all(),
            'value' => $user['country']
        ));
        if ($login_admin) {
            $fieldset = $form->add('fieldset', array(
                'title' => '{LNG_Other}'
            ));
            // status
            $fieldset->add('select', array(
                'id' => 'register_status',
                'itemClass' => 'item',
                'label' => '{LNG_Member status}',
                'labelClass' => 'g-input icon-star0',
                'disabled' => $login_admin['id'] == $user['id'] ? true : false,
                'options' => self::$cfg->member_status,
                'value' => $user['status']
            ));
            // permission
            $fieldset->add('checkboxgroups', array(
                'id' => 'register_permission',
                'label' => '{LNG_Permission}',
                'labelClass' => 'g-input icon-list',
                'options' => \Gcms\Controller::getPermissions(),
                'value' => $user['permission']
            ));
        }
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        $fieldset->add('hidden', array(
            'id' => 'register_id',
            'value' => $user['id']
        ));
        $form->script("countryChanged('register');");
        $form->script("birthdayChanged('register_birthday', '%s ({LNG_age} %y {LNG_year}, %m {LNG_month} %d {LNG_days})');");
        return $form->render();
    }
}
