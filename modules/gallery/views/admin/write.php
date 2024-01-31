<?php
/**
 * @filesource modules/gallery/views/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Write;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=gallery-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มสร้าง/แก้ไข อัลบัม
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
            'action' => 'index.php/gallery/model/admin/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Album}'
        ));
        // topic
        $fieldset->add('text', array(
            'id' => 'topic',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Album}',
            'comment' => '{LNG_Enter the name of the album}',
            'maxlength' => 255,
            'value' => $index->topic
        ));
        // detail
        $fieldset->add('textarea', array(
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Detail}',
            'comment' => '{LNG_Fill out a short description About the photo albums or photos}',
            'rows' => 5,
            'value' => $index->detail
        ));
        // create_date
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_date}'
        ));
        // last_update
        $row = $groups->add('row');
        $row->add('date', array(
            'id' => 'last_update_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width',
            'value' => date('Y-m-d', $index->last_update)
        ));
        $row->add('time', array(
            'id' => 'last_update_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width',
            'label' => '{LNG_Time}',
            'value' => date('H:i', $index->last_update)
        ));
        // image
        if (!empty($index->image) && is_file(ROOT_PATH.DATA_FOLDER.'gallery/'.$index->id.'/'.$index->image)) {
            $img = WEB_URL.DATA_FOLDER.'gallery/'.$index->id.'/'.$index->image;
        } else {
            $img = WEB_URL.'modules/gallery/img/noimage.jpg';
        }
        $fieldset->add('file', array(
            'id' => 'image',
            'labelClass' => 'g-input icon-thumbnail',
            'itemClass' => 'item',
            'label' => '{LNG_Cover image}',
            'comment' => '{LNG_Upload a photo no larger than :width pixels :type types only larger than a specified size will be resize automatically}',
            'dataPreview' => 'imgPicture',
            'previewSrc' => $img,
            'accept' => $index->img_typies
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
            '/:type/' => implode(', ', $index->img_typies),
            '/:width/' => $index->image_width
        ));
        return $form->render();
    }
}
