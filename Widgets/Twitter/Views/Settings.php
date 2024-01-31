<?php
/**
 * @filesource Widgets/Twitter/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Twitter\Views;

use Kotchasan\Html;

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
     * module=Twitter-Settings
     *
     * @return string
     */
    public function render()
    {
        if (empty(self::$cfg->twitter)) {
            self::$cfg->twitter = \Widgets\Twitter\Models\Settings::defaultSettings();
        }
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/Widgets/Twitter/Models/Settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Configuring}'
        ));
        // id
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Twitter ID}',
            'comment' => '{LNG_Twitter widget ID, can be found from the URL displayed on the browser Addressbar when visited your Widget settngs}'
        ));
        $groups->add('label', array(
            'for' => 'twitter_id',
            'innerHTML' => 'https://twitter.com/settings/widgets/'
        ));
        $groups->add('text', array(
            'id' => 'twitter_id',
            'labelClass' => 'g-input',
            'itemClass' => 'width',
            'value' => self::$cfg->twitter['id']
        ));
        $groups->add('span', array(
            'innerHTML' => '/edit'
        ));
        // user
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Twitter Name}',
            'comment' => '{LNG_Enter your Twitter username. By entering the site and go to your Profile in the Address Bar will appear on your Twitter account name}'
        ));
        $groups->add('label', array(
            'for' => 'twitter_user',
            'innerHTML' => 'https://twitter.com/'
        ));
        $groups->add('text', array(
            'id' => 'twitter_user',
            'labelClass' => 'g-input',
            'itemClass' => 'width',
            'value' => self::$cfg->twitter['user']
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Set to display the}'
        ));
        // height
        $fieldset->add('number', array(
            'id' => 'twitter_height',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'item',
            'label' => '{LNG_Height}',
            'comment' => '{LNG_The size of the widget} ({LNG_pixel})',
            'value' => self::$cfg->twitter['height']
        ));
        // amount
        $fieldset->add('number', array(
            'id' => 'twitter_amount',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Number}',
            'comment' => '{LNG_Determine the maximum number of messages to be displayed (set to 0 to display the Scrollbar)}',
            'value' => self::$cfg->twitter['amount']
        ));
        // theme
        $fieldset->add('select', array(
            'id' => 'twitter_theme',
            'labelClass' => 'g-input icon-template',
            'itemClass' => 'item',
            'label' => '{LNG_Theme}',
            'comment' => '{LNG_Twitter message box styles}',
            'options' => array('light' => '{LNG_Light}', 'dark' => '{LNG_Dark}'),
            'value' => self::$cfg->twitter['theme']
        ));
        // border_color
        $fieldset->add('color', array(
            'id' => 'twitter_border_color',
            'labelClass' => 'g-input icon-color',
            'itemClass' => 'item',
            'label' => '{LNG_Border color}',
            'value' => self::$cfg->twitter['border_color']
        ));
        // link_color
        $fieldset->add('color', array(
            'id' => 'twitter_link_color',
            'labelClass' => 'g-input icon-color',
            'itemClass' => 'item',
            'label' => '{LNG_Link Color}',
            'value' => self::$cfg->twitter['link_color']
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
            'style' => 'height:'.self::$cfg->twitter['height'].'px;max-width:300px;',
            'innerHTML' => \Widgets\Twitter\Views\Index::render(self::$cfg->twitter)
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
