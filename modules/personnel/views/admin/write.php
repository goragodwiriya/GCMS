<?php
/**
 * @filesource modules/personnel/views/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Admin\Write;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\Http\Request;

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
     * ฟอร์มสร้าง/แก้ไข บุคลากร
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
            'action' => 'index.php/personnel/model/admin/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Personnel}'
        ));
        // name
        $fieldset->add('text', array(
            'id' => 'name',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'item',
            'label' => '{LNG_Name} {LNG_Surname}',
            'placeholder' => '{LNG_Please fill in} {LNG_Name} {LNG_Surname}',
            'maxlength' => 50,
            'value' => isset($index->name) ? $index->name : ''
        ));
        // category_id
        $fieldset->add('select', array(
            'id' => 'category_id',
            'labelClass' => 'g-input icon-group',
            'label' => '{LNG_Personnel groups}',
            'itemClass' => 'item',
            'options' => array(0 => '{LNG_Uncategorized}')+\Index\Category\Model::categories((int) $index->module_id),
            'value' => isset($index->category_id) ? $index->category_id : 0
        ));
        // order
        $fieldset->add('number', array(
            'id' => 'order',
            'labelClass' => 'g-input icon-sort',
            'itemClass' => 'item',
            'label' => '{LNG_Sort}',
            'comment' => '{LNG_Enter more than zero, used to prioritize. The number one is the highest priority as administrators.}',
            'value' => isset($index->order) ? $index->order : 0
        ));
        // position
        $fieldset->add('text', array(
            'id' => 'position',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Position}',
            'maxlength' => 100,
            'value' => isset($index->position) ? $index->position : ''
        ));
        // detail
        $fieldset->add('text', array(
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Detail}',
            'maxlength' => 255,
            'value' => isset($index->detail) ? $index->detail : ''
        ));
        // address
        $fieldset->add('text', array(
            'id' => 'address',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'item',
            'label' => '{LNG_Address}',
            'maxlength' => 255,
            'value' => isset($index->address) ? $index->address : ''
        ));
        // phone
        $fieldset->add('text', array(
            'id' => 'phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'item',
            'label' => '{LNG_Phone}',
            'maxlength' => 20,
            'value' => isset($index->phone) ? $index->phone : ''
        ));
        // email
        $fieldset->add('text', array(
            'id' => 'email',
            'labelClass' => 'g-input icon-email',
            'itemClass' => 'item',
            'label' => '{LNG_Email}',
            'maxlength' => 255,
            'value' => isset($index->email) ? $index->email : ''
        ));
        // picture
        if (!empty($index->picture) && is_file(ROOT_PATH.DATA_FOLDER.'personnel/'.$index->picture)) {
            $img = WEB_URL.DATA_FOLDER.'personnel/'.$index->picture;
        } else {
            $img = WEB_URL.'modules/personnel/img/noimage.jpg';
        }
        $fieldset->add('file', array(
            'id' => 'picture',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Image}',
            'comment' => '{LNG_Browse image uploaded, type :type size :width*:height pixel} ({LNG_automatic resize})',
            'dataPreview' => 'imgPicture',
            'previewSrc' => $img
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
            '/:type/' => 'jpg, jpeg, png, gif',
            '/:width/' => $index->image_width,
            '/:height/' => $index->image_height
        ));
        return $form->render();
    }
}
