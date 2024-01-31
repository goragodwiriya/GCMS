<?php
/**
 * @filesource modules/index/views/modulepage.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Modulepage;

use Gcms\Gcms;
use Kotchasan\ArrayTool;
use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=modulepage
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มสร้าง/แก้ไข หน้าเว็บไซต์
     *
     * @return string
     */
    public function render($index)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/modulepage/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'upload' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Module page}'
        ));
        // language
        $fieldset->add('select', array(
            'id' => 'language',
            'label' => '{LNG_Language}',
            'labelClass' => 'g-input icon-language',
            'itemClass' => 'item',
            'comment' => '{LNG_Select the language of this item (Select the first Is present in every language)}',
            'options' => ArrayTool::replace(array('' => '{LNG_all languages}'), Language::installedLanguage()),
            'value' => $index->language
        ));
        // module
        $modules = array();
        foreach (Gcms::$module->getInstalledModules() as $item) {
            $modules[$item->module] = $item->module;
        }
        // module
        $fieldset->add('select', array(
            'id' => 'module',
            'labelClass' => 'g-input icon-modules',
            'itemClass' => 'item',
            'label' => '{LNG_Module}',
            'comment' => '{LNG_List all installed modules available} (index.php?module=<em>modulename</em>-page)',
            'options' => $modules,
            'value' => $index->module
        ));
        // page
        $fieldset->add('text', array(
            'id' => 'page',
            'labelClass' => 'g-input icon-index',
            'itemClass' => 'item',
            'label' => '{LNG_Webpage}',
            'comment' => '{LNG_The web page of the module you want to edit is located in the Address bar when the page is called} (index.php?module=modulename-<em>page</em>)',
            'maxlength' => 64,
            'value' => $index->page
        ));
        // topic
        $fieldset->add('text', array(
            'id' => 'topic',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Topic}',
            'comment' => '{LNG_Text displayed on the Title Bar of the browser (3 - 255 characters)}',
            'maxlength' => 255,
            'value' => $index->topic
        ));
        // keywords
        $fieldset->add('textarea', array(
            'id' => 'keywords',
            'labelClass' => 'g-input icon-tags',
            'itemClass' => 'item',
            'label' => '{LNG_Keywords}',
            'comment' => '{LNG_Text keywords for SEO or Search Engine to search}',
            'value' => $index->keywords
        ));
        // description
        $fieldset->add('textarea', array(
            'id' => 'description',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Description}',
            'comment' => '{LNG_Text short summary of your story. Which can be used to show in your theme.}',
            'value' => $index->description
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
            'value' => $index->id
        ));
        return $form->render();
    }
}
