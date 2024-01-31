<?php
/**
 * @filesource modules/document/views/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Write;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=document-write
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
            'action' => 'index.php/document/model/admin/write/submit',
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
            $fieldset->add('inputgroups', array(
                'id' => 'keywords_'.$item,
                'labelClass' => 'g-input icon-tags',
                'itemClass' => 'item',
                'label' => '{LNG_Keywords}',
                'comment' => '{LNG_Text keywords for SEO or Search Engine to search}',
                'value' => $details->keywords == '' ? array() : explode(',', $details->keywords)
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
                'value' => str_replace('{WEBURL}', WEB_URL, $details->detail)
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
            'comment' => '{LNG_Used for the URL of the web page (SEO) can use letters, numbers and - only can not have duplicate names.}',
            'value' => $index->alias
        ));
        // create_date
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Article Date}',
            'comment' => '{LNG_The date that the story was written}'
        ));
        $row = $groups->add('row');
        $row->add('date', array(
            'id' => 'create_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width',
            'value' => date('Y-m-d', $index->create_date)
        ));
        $row->add('time', array(
            'id' => 'create_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width',
            'label' => '{LNG_Time}',
            'value' => date('H:i', $index->create_date)
        ));
        // picture
        if (!empty($index->picture) && is_file(ROOT_PATH.DATA_FOLDER.'document/'.$index->picture)) {
            $img = WEB_URL.DATA_FOLDER.'document/'.$index->picture;
        } else {
            $img = WEB_URL.(isset($index->default_icon) ? $index->default_icon : 'modules/document/img/document-icon.png');
        }
        $comment = Language::trans(Language::replace('Browse image uploaded, type :type size :width*:height pixel', array(
            ':type' => implode(', ', $index->img_typies),
            ':width' => $index->icon_width,
            ':height' => $index->icon_height
        )).' ({LNG_automatic resize})');
        $fieldset->add('file', array(
            'id' => 'picture',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Thumbnail}',
            'comment' => $comment,
            'dataPreview' => 'imgPicture',
            'previewSrc' => $img
        ));
        // category_id
        $fieldset->add('select', array(
            'id' => 'category_'.$index->module_id,
            'name' => 'category_id',
            'labelClass' => 'g-input icon-category',
            'label' => '{LNG_Category}',
            'comment' => '{LNG_Select the category you want}',
            'itemClass' => 'item',
            'options' => array(0 => '{LNG_Uncategorized}')+\Index\Category\Model::categories((int) $index->module_id, 0, false),
            'value' => isset($index->category_id) ? $index->category_id : 0
        ));
        // can_reply
        $fieldset->add('select', array(
            'id' => 'can_reply',
            'labelClass' => 'g-input icon-comments',
            'itemClass' => 'item',
            'label' => '{LNG_Comment}',
            'comment' => '{LNG_Comment the story}',
            'options' => Language::get('REPLIES'),
            'value' => $index->can_reply
        ));
        // show_news
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Display in the widget} <a href="http://gcms.in.th/index.php?module=howto&id=311" target=_blank class=icon-help></a>',
            'comment' => '{LNG_Use this option if you want a list that is presented in part by itself.}'
        ));
        foreach (Language::get('SHOW_NEWS') as $key => $value) {
            $groups->add('checkbox', array(
                'id' => 'show_news_'.$key,
                'name' => 'show_news[]',
                'itemClass' => 'width',
                'label' => $value,
                'value' => $key,
                'checked' => strpos($index->show_news, "$key=1") === false ? false : true
            ));
        }
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
        // preview
        $fieldset->add('button', array(
            'id' => 'preview',
            'class' => 'button preview large icon-index',
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
        // tab
        $fieldset->add('hidden', array(
            'id' => 'tab',
            'value' => $tab
        ));
        $form->script('initDocumentWrite(["'.implode('", "', $index->languages).'"], "'.$tab.'", '.$index->module_id.', "'.$index->module.'");');
        return $form->render();
    }
}
