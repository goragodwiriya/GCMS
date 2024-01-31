<?php
/**
 * @filesource modules/edocument/views/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Admin\Write;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Text;

/**
 * module=edocument-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มสร้าง/แก้ไข เอกสาร
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
            'action' => 'index.php/edocument/model/admin/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_E-Document}'
        ));
        // document_no
        $fieldset->add('text', array(
            'id' => 'document_no',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Document number}',
            'comment' => '{LNG_Enter the number of documents For reference}',
            'maxlength' => 20,
            'value' => isset($index->document_no) ? $index->document_no : ''
        ));
        // reciever
        $fieldset->add('checkboxgroups', array(
            'id' => 'reciever',
            'name' => 'reciever[]',
            'labelClass' => 'g-input icon-group',
            'itemClass' => 'item',
            'label' => '{LNG_Select the group of recipients}',
            'comment' => '{LNG_The recipient is listed in the selected group can be downloaded (You can select multiple groups)}',
            'options' => array(-1 => '{LNG_Guest}') + self::$cfg->member_status,
            'value' => $index->reciever
        ));
        // topic
        $fieldset->add('text', array(
            'id' => 'topic',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Document title}',
            'comment' => '{LNG_The name of the file you want to download it the file name extension. To upload a new file name if not specified. The program uses the name of a file to upload. (Can be used in Thai language)}',
            'maxlength' => 50,
            'value' => isset($index->topic) ? $index->topic : ''
        ));
        // file
        $fieldset->add('file', array(
            'id' => 'file',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'comment' => '{LNG_Upload :type files no larger than :size}',
            'accept' => $index->file_typies
        ));
        // detail
        $fieldset->add('textarea', array(
            'id' => 'detail',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Description}',
            'comment' => '{LNG_Notes or Additional Notes}',
            'rows' => 5,
            'value' => isset($index->detail) ? $index->detail : ''
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        $fieldset->add('checkbox', array(
            'id' => 'send_mail',
            'label' => '{LNG_Send an email to members}',
            'checked' => $index->send_mail && $index->id == 0,
            'value' => 1
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
            '/:type/' => implode(', ', $index->file_typies),
            '/:size/' => Text::formatFileSize($index->upload_size)
        ));
        return $form->render();
    }
}
