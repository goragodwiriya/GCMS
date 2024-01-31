<?php
/**
 * @filesource modules/documentation/views/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Admin\Write;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=documentation-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มสร้าง/แก้ไข เนื้อหา
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/documentation/model/admin/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        foreach ($index->languages as $item) {
            // รายละเอียด
            $details = isset($index->details[$item]) ? $index->details[$item] : (object) array('topic' => '', 'keywords' => '', 'description' => '', 'detail' => '', 'relate' => '');
            // รายละเอียดแต่ละภาษา
            $fieldset = $form->add('fieldset', array(
                'id' => 'detail_'.$item,
                'title' => '{LNG_Detail}&nbsp;<img src='.WEB_URL.'language/'.$item.'.gif alt='.$item.'>'
            ));
            // topic
            $fieldset->add('text', array(
                'id' => 'topic_'.$item,
                'labelClass' => 'g-input icon-edit',
                'itemClass' => 'item',
                'label' => '{LNG_Topic}',
                'comment' => '{LNG_Title or topic 3 to 255 characters}',
                'maxlength' => 255,
                'value' => $details->topic
            ));
            // keywords
            $fieldset->add('textarea', array(
                'id' => 'keywords_'.$item,
                'labelClass' => 'g-input icon-tags',
                'itemClass' => 'item',
                'label' => '{LNG_Keywords}',
                'comment' => '{LNG_Text keywords for SEO or Search Engine to search}',
                'value' => $details->keywords
            ));
            // relate
            $fieldset->add('text', array(
                'id' => 'relate_'.$item,
                'labelClass' => 'g-input icon-edit',
                'itemClass' => 'item',
                'label' => '{LNG_Relate}',
                'comment' => '{LNG_Used to group similar contents} ({LNG_Separate them with a comma})',
                'value' => $details->relate
            ));
            // description
            $fieldset->add('textarea', array(
                'id' => 'description_'.$item,
                'labelClass' => 'g-input icon-file',
                'itemClass' => 'item',
                'label' => '{LNG_Description}',
                'comment' => '{LNG_Text short summary of your story. Which can be used to show in your theme.} ({LNG_If not, Program will fill in the contents of the first paragraph})',
                'value' => $details->description
            ));
            // detail
            $fieldset->add('ckeditor', array(
                'id' => 'details_'.$item,
                'itemClass' => 'item',
                'height' => 300,
                'language' => Language::name(),
                'toolbar' => 'Document',
                'upload' => true,
                'label' => '{LNG_Detail}',
                'value' => $details->detail
            ));
        }
        // รายละเอียดอื่นๆ
        $fieldset = $form->add('fieldset', array(
            'id' => 'options',
            'title' => '{LNG_Set up or configure other details}'
        ));
        // alias
        $fieldset->add('text', array(
            'id' => 'alias',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'item',
            'label' => '{LNG_Alias}',
            'comment' => '{LNG_Used for the URL of the web page (SEO) can use letters, numbers and _ only can not have duplicate names.}',
            'value' => $index->alias
        ));
        // category_id
        $fieldset->add('select', array(
            'id' => 'category_'.$index->module_id,
            'name' => 'category_id',
            'labelClass' => 'g-input icon-category',
            'label' => '{LNG_Category}',
            'comment' => '{LNG_Select the category you want}',
            'itemClass' => 'item',
            'options' => array(0 => '{LNG_Uncategorized}')+\Index\Category\Model::categories((int) $index->module_id),
            'value' => $index->category_id
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
        // preview
        $fieldset->add('button', array(
            'id' => 'preview',
            'class' => 'button preview large',
            'value' => '{LNG_Preview}'
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id
        ));
        // module_id
        $fieldset->add('hidden', array(
            'id' => 'module_id',
            'value' => $index->module_id
        ));
        // tab ที่เลือก
        $tab = $request->request('tab')->toString();
        $tab = empty($tab) ? 'detail_'.reset($index->languages) : $tab;
        $form->script('initWriteTab("accordient_menu", "'.$tab.'");');
        $form->script('checkSaved("preview", "'.WEB_URL.'index.php?module='.$index->module.'", "id");');
        $form->script('new GValidator("alias", "keyup,change", checkAlias, "index.php/index/model/checker/alias", null, "setup_frm");');
        // tab
        $fieldset->add('hidden', array(
            'id' => 'tab',
            'value' => $tab
        ));
        return $form->render();
    }
}
