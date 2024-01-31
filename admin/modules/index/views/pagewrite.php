<?php
/**
 * @filesource modules/index/views/pagewrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Pagewrite;

use Kotchasan\ArrayTool;
use Kotchasan\Html;
use Kotchasan\Language;

/**
 * ฟอร์มสร้าง/แก้ไข หน้าเว็บไซต์
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * module=pagewrite
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
            'action' => 'index.php/index/model/pagewrite/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'upload' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} '.Language::get($index->owner === 'index' ? 'Page' : 'Module')
        ));
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Language}',
            'for' => 'language',
            'comment' => '{LNG_Select the language of this item (Select the first Is present in every language)}'
        ));
        // language
        $groups->add('select', array(
            'id' => 'language',
            'labelClass' => 'g-input icon-language',
            'itemClass' => 'width',
            'options' => ArrayTool::replace(array('' => '{LNG_all languages}'), Language::installedLanguage()),
            'value' => $index->language
        ));
        $groups->add('a', array(
            'id' => 'btn_copy',
            'class' => 'button icon-copy copy',
            'title' => '{LNG_Copy this item to the selected language}'
        ));
        // module
        $fieldset->add('text', array(
            'id' => 'module',
            'labelClass' => 'g-input icon-modules',
            'itemClass' => 'item',
            'label' => '{LNG_Module}',
            'comment' => '{LNG_Name of this module. English lowercase and number only, short. (Can not use a reserve or a duplicate)}',
            'maxlength' => 64,
            'value' => $index->module
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
            'comment' => '{LNG_Text short summary of your story. Which can be used to show in your theme.} ({LNG_If not, Program will fill in the contents of the first paragraph})',
            'value' => $index->description
        ));
        // detail
        $fieldset->add('ckeditor', array(
            'id' => 'detail',
            'itemClass' => 'item',
            'height' => 300,
            'language' => Language::name(),
            'toolbar' => 'Document',
            'upload' => true,
            'label' => '{LNG_Detail}',
            'value' => str_replace('{WEBURL}', WEB_URL, $index->detail)
        ));
        // published_date
        $fieldset->add('date', array(
            'id' => 'published_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Published date}',
            'comment' => '{LNG_The date of publication of this information. The publisher will start automatically when you log on due date}',
            'value' => $index->published_date
        ));
        // published
        $fieldset->add('select', array(
            'id' => 'published',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Published}',
            'comment' => '{LNG_Publish this item}',
            'options' => Language::get('PUBLISHEDS'),
            'value' => $index->published
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // preview button
        if ($index->owner == 'index') {
            $fieldset->add('button', array(
                'id' => 'btn_preview',
                'class' => 'button preview large icon-index',
                'value' => '{LNG_Preview}'
            ));
        }
        // owner
        $fieldset->add('hidden', array(
            'id' => 'owner',
            'value' => $index->owner
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id
        ));
        $form->script('initIndexWrite();');
        return $form->render();
    }
}
