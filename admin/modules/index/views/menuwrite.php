<?php
/**
 * @filesource modules/index/views/menuwrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menuwrite;

use Kotchasan\ArrayTool;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * ฟอร์มสร้าง/แก้ไข เมนู
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * module=menuwrite
     *
     * @param Request $request
     * @param object  $menu
     *
     * @return string
     */
    public function render(Request $request, $menu)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/menuwrite/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Menu details}'
        ));
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Language}',
            'id' => 'language',
            'comment' => '{LNG_Select the language of this item (Select the first Is present in every language)}'
        ));
        // language
        $groups->add('select', array(
            'id' => 'language',
            'labelClass' => 'g-input icon-language',
            'itemClass' => 'width',
            'options' => ArrayTool::replace(array('' => '{LNG_all languages}'), Language::installedLanguage()),
            'value' => empty($menu->id) ? '' : $menu->language
        ));
        $groups->add('a', array(
            'id' => 'copy_menu',
            'class' => 'button icon-copy copy',
            'title' => '{LNG_Copy this item to the selected language}'
        ));
        // menu_text
        $fieldset->add('text', array(
            'id' => 'menu_text',
            'labelClass' => 'g-input icon-menus',
            'itemClass' => 'item',
            'label' => '{LNG_Text}',
            'comment' => '{LNG_Text displayed on the menu}',
            'maxlength' => 100,
            'value' => $menu->menu_text
        ));
        // menu_tooltip
        $fieldset->add('text', array(
            'id' => 'menu_tooltip',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Tooltip}',
            'comment' => '{LNG_Message when mouse over the menu}',
            'maxlength' => 100,
            'value' => $menu->menu_tooltip
        ));
        // accesskey
        $fieldset->add('text', array(
            'id' => 'accesskey',
            'labelClass' => 'g-input icon-keyboard',
            'itemClass' => 'item',
            'label' => '{LNG_Accesskey}',
            'comment' => '{LNG_Enter lowercase English letters or numbers to be used as a shortcut to this menu. (Sub-menus do not support the shortcut menu. Do not duplicate keys of the system shortcut)}',
            'value' => $menu->accesskey
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Installation and position of the menu}'
        ));
        // alias
        $fieldset->add('text', array(
            'id' => 'alias',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Alias}',
            'comment' => '{LNG_The name of the menu (the default is the name of the module is installed)}',
            'value' => $menu->alias
        ));
        // parent
        $fieldset->add('select', array(
            'id' => 'parent',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Menu position}',
            'comment' => '{LNG_Select the menu position. The menu will be displayed on the website at the selected position. (Based on templates you are using)}',
            'options' => Language::get('MENU_PARENTS'),
            'value' => $menu->parent
        ));
        // type
        if ($menu->menu_order == 1) {
            $m = 0;
        } elseif ($menu->level == 0) {
            $m = 1;
        } elseif ($menu->level == 1) {
            $m = 2;
        } else {
            $m = 3;
        }
        $fieldset->add('select', array(
            'id' => 'type',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Menu type}',
            'comment' => '{LNG_Select the type of menu}',
            'options' => Language::get('MENU_TYPES'),
            'value' => $m
        ));
        // menu_order
        $fieldset->add('select', array(
            'id' => 'menu_order',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Menu order}',
            'size' => 8,
            'comment' => '{LNG_The sequence of the desired menu. The menu will be displayed next from the selected item}'
        ));
        // published
        $fieldset->add('select', array(
            'id' => 'published',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Status}',
            'comment' => '{LNG_Publish this item}',
            'options' => Language::get('MENU_PUBLISHEDS'),
            'value' => $menu->published
        ));
        $fieldset = $form->add('fieldset', array(
            'id' => 'menu_action',
            'title' => '{LNG_Action when click on menu}'
        ));
        // action
        if ($menu->menu_url != '') {
            $m = 2;
        } elseif ($menu->index_id == 0) {
            $m = 0;
        } else {
            $m = 1;
        }
        $fieldset->add('select', array(
            'id' => 'action',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_When choosing the menu}',
            'comment' => '{LNG_Choose how to proceed. When you click on the menu}',
            'options' => Language::get('MENU_ACTIONS'),
            'value' => $m
        ));
        // index_id
        $fieldset->add('select', array(
            'id' => 'index_id',
            'labelClass' => 'g-input icon-modules',
            'itemClass' => 'item action 1',
            'label' => '{LNG_Installed module}',
            'comment' => '{LNG_Choose the page you want to open when you click a menu item from a list of web pages or modules already installed}',
            'optgroup' => \Index\Menuwrite\Model::getModules(),
            'value' => $menu->owner.'_'.$menu->module.'_'.$menu->index_id
        ));
        // menu_url
        $fieldset->add('text', array(
            'id' => 'menu_url',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'item action 2',
            'label' => '{LNG_URL}',
            'comment' => '{LNG_Links for this item, which will open this page when click on it}',
            'value' => str_replace(array('{', '}'), array('&#x007B;', '&#x007D;'), $menu->menu_url)
        ));
        // menu_target
        $fieldset->add('select', array(
            'id' => 'menu_target',
            'labelClass' => 'g-input icon-forward',
            'itemClass' => 'item action 1 2',
            'label' => '{LNG_The opening page of links}',
            'comment' => '{LNG_Determine how to turn the page when a link is clicked}',
            'options' => Language::get('MENU_TARGET'),
            'value' => $menu->menu_target
        ));
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
            'id' => 'id',
            'value' => $menu->id
        ));
        $form->script('initMenuwrite();');
        return $form->render();
    }
}
