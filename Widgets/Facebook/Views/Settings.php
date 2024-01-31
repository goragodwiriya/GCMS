<?php
/**
 * @filesource Widgets/Facebook/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Facebook\Views;

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
     * module=Facebook-Settings
     *
     * @return string
     */
    public function render()
    {
        if (empty(self::$cfg->facebook_page)) {
            self::$cfg->facebook_page = \Widgets\Facebook\Models\Settings::defaultSettings();
        }
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/Widgets/Facebook/Models/Settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Set to display the} {LNG_Facebook page}'
        ));
        // height
        $fieldset->add('number', array(
            'id' => 'height',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'item',
            'label' => '{LNG_Height}',
            'comment' => '{LNG_The size of the widget} ({LNG_more than} 70 {LNG_pixel})',
            'value' => self::$cfg->facebook_page['height']
        ));
        // user
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Username}',
            'comment' => '{LNG_Facebook profile username eg https://www.facebook.com/<em>username</em>}'
        ));
        $groups->add('label', array(
            'for' => 'user',
            'innerHTML' => 'https://www.facebook.com/'
        ));
        $groups->add('text', array(
            'id' => 'user',
            'labelClass' => 'g-input icon-facebook',
            'itemClass' => 'width',
            'value' => self::$cfg->facebook_page['user']
        ));
        // show_facepile
        $fieldset->add('select', array(
            'id' => 'show_facepile',
            'labelClass' => 'g-input icon-users',
            'itemClass' => 'item',
            'label' => '{LNG_Friend&#039;s faces}',
            'options' => Language::get('BOOLEANS'),
            'value' => self::$cfg->facebook_page['show_facepile']
        ));
        // hide_cover
        $fieldset->add('select', array(
            'id' => 'hide_cover',
            'labelClass' => 'g-input icon-image',
            'itemClass' => 'item',
            'label' => '{LNG_Cover image}',
            'options' => Language::get('BOOLEANS'),
            'value' => self::$cfg->facebook_page['hide_cover']
        ));
        // small_header
        $fieldset->add('select', array(
            'id' => 'small_header',
            'labelClass' => 'g-input icon-image',
            'itemClass' => 'item',
            'label' => '{LNG_Small header}',
            'options' => Language::get('BOOLEANS'),
            'value' => self::$cfg->facebook_page['small_header']
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        $form->add('div', array(
            'class' => 'margin-top-right-bottom-left',
            'innerHTML' => \Widgets\Facebook\Views\Index::render(self::$cfg->facebook_page)
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
