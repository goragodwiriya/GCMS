<?php
/**
 * @filesource modules/video/views/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Video\Admin\Write;

use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=video-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มสร้าง/แก้ไข Video
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
            'action' => 'index.php/video/model/admin/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Video}'
        ));
        $group = $fieldset->add('groups-table', array(
            'label' => '{LNG_Youtube ID}',
            'for' => 'write_youtube',
            'comment' => '{LNG_Enter the ID of the video from Youtube 11 characters eg 17IKhjQWT9M (Without a complete URL)}'
        ));
        $group->add('label', array(
            'class' => 'width',
            'for' => 'write_youtube',
            'innerHTML' => 'http://www.youtube.com/watch?v='
        ));
        // youtube
        $group->add('text', array(
            'id' => 'write_youtube',
            'itemClass' => 'width',
            'maxlength' => 11,
            'placeholder' => '17IKhjQWT9M',
            'value' => isset($index->youtube) ? $index->youtube : ''
        ));
        // thumb
        $thumb = isset($index->youtube) && is_file(ROOT_PATH.DATA_FOLDER.'video/'.$index->youtube.'.jpg') ? WEB_URL.DATA_FOLDER.'video/'.$index->youtube.'.jpg' : '../modules/video/img/nopicture.jpg';
        $fieldset->add('div', array(
            'class' => 'item',
            'innerHTML' => '<div class=usericon><img src="'.$thumb.'" id=imgIcon></div>'
        ));
        // topic
        $fieldset->add('text', array(
            'id' => 'write_topic',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Topic}',
            'comment' => '{LNG_The name of the video. (If not, proceed to take the title from Youtube).}',
            'value' => isset($index->topic) ? $index->topic : ''
        ));
        // description
        $fieldset->add('textarea', array(
            'id' => 'write_description',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Description}',
            'comment' => '{LNG_A short description about the video. (If you do not fill in the details from Youtube).}',
            'rows' => 5,
            'value' => isset($index->description) ? $index->description : ''
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
            'id' => 'write_id',
            'value' => $index->id
        ));
        // module_id
        $fieldset->add('hidden', array(
            'id' => 'module_id',
            'value' => $index->module_id
        ));
        return $form->render();
    }
}
