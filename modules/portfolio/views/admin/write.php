<?php
/**
 * @filesource modules/portfolio/views/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Portfolio\Admin\Write;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=portfolio-write
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
            'action' => 'index.php/portfolio/model/admin/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Portfolio}'
        ));
        // title
        $fieldset->add('text', array(
            'id' => 'title',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Topic}',
            'comment' => '{LNG_Title or topic 3 to 255 characters}',
            'maxlength' => 255,
            'value' => isset($index->title) ? $index->title : ''
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
            'value' => isset($index->detail) ? $index->detail : ''
        ));
        // keywords
        $fieldset->add('text', array(
            'id' => 'keywords',
            'labelClass' => 'g-input icon-tags',
            'itemClass' => 'item',
            'label' => '{LNG_Tags}',
            'comment' => '{LNG_Used to group similar contents} ({LNG_Separate them with a comma})',
            'value' => isset($index->keywords) ? $index->keywords : ''
        ));
        // create_date
        $fieldset->add('date', array(
            'id' => 'create_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Published date}',
            'comment' => '{LNG_The date of this For display on site}',
            'value' => date('Y-m-d', isset($index->create_date) ? $index->create_date : time())
        ));
        // url
        $fieldset->add('url', array(
            'id' => 'url',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'item',
            'label' => '{LNG_URL}',
            'comment' => '{LNG_URL for this entry}',
            'placeholder' => WEB_URL,
            'value' => isset($index->url) ? $index->url : ''
        ));
        // thumbnail
        if (!empty($index->id) && is_file(ROOT_PATH.DATA_FOLDER.'portfolio/thumb_'.$index->id.'.jpg')) {
            $img = WEB_URL.DATA_FOLDER.'portfolio/thumb_'.$index->id.'.jpg';
        } else {
            $img = WEB_URL.'skin/img/blank.gif';
        }
        $fieldset->add('file', array(
            'id' => 'thumbnail',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Thumbnail}',
            'comment' => '{LNG_Browse image uploaded, type :type size :width*:height pixel} ({LNG_automatic resize})',
            'dataPreview' => 'imgThumbnail',
            'previewSrc' => $img
        ));
        // image
        if (!empty($index->image) && is_file(ROOT_PATH.DATA_FOLDER.'portfolio/'.$index->image)) {
            $img = WEB_URL.DATA_FOLDER.'portfolio/'.$index->image;
        } else {
            $img = WEB_URL.'skin/img/blank.gif';
        }
        $fieldset->add('file', array(
            'id' => 'image',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Image}',
            'comment' => '{LNG_Select image for the full size, types :type only.}',
            'dataPreview' => 'imgPicture',
            'previewSrc' => $img
        ));
        // published
        $fieldset->add('select', array(
            'id' => 'published',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Published}',
            'comment' => '{LNG_Publish this item}',
            'options' => Language::get('PUBLISHEDS'),
            'value' => isset($index->published) ? $index->published : 1
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
        // module_id
        $fieldset->add('hidden', array(
            'id' => 'module_id',
            'value' => $index->module_id
        ));
        Gcms::$view->setContentsAfter(array(
            '/:type/' => 'jpg, jpeg, png',
            '/:width/' => $index->width,
            '/:height/' => $index->height
        ));
        return $form->render();
    }
}
