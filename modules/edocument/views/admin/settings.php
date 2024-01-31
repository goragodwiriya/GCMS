<?php
/**
 * @filesource modules/edocument/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Admin\Settings;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=edocument-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * จัดการการตั้งค่าโมดูล
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
            'action' => 'index.php/edocument/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Module settings}'
        ));
        // format_no
        $fieldset->add('text', array(
            'id' => 'format_no',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Document number}',
            'comment' => '{LNG_Specify the format of the document number as% 04d means adding zeros until the four-digit number on the front, such as 0001.}',
            'value' => isset($index->format_no) ? $index->format_no : ''
        ));
        // send_mail
        $fieldset->add('select', array(
            'id' => 'send_mail',
            'labelClass' => 'g-input icon-email',
            'itemClass' => 'item',
            'label' => '{LNG_Emailing}',
            'comment' => '{LNG_When adding a new document Email alert to the recipient. When enabled this option.}',
            'options' => Language::get('BOOLEANS'),
            'value' => isset($index->send_mail) ? $index->send_mail : 1
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Upload}'
        ));
        // file_typies
        $fieldset->add('text', array(
            'id' => 'file_typies',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Specify the file extension that allows uploading. English lowercase letters and numbers 2-4 characters to separate each type with a comma (,) and without spaces. eg zip,rar,doc,docx}',
            'value' => isset($index->file_typies) ? implode(',', $index->file_typies) : 'doc,ppt,pptx,docx,rar,zip,jpg,pdf'
        ));
        // upload_size
        $sizes = array();
        foreach (array(2, 4, 6, 8, 16, 32, 64, 128, 256, 512, 1024, 2048) as $i) {
            $a = $i * 1048576;
            $sizes[$a] = Text::formatFileSize($a);
        }
        $fieldset->add('select', array(
            'id' => 'upload_size',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Size of the file upload}',
            'comment' => '{LNG_The size of the files can be uploaded. (Should not exceed the value of the Server :upload_max_filesize.)}',
            'options' => $sizes,
            'value' => isset($index->upload_size) ? $index->upload_size : ':upload_max_filesize'
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Download}'
        ));
        // download_action
        $fieldset->add('select', array(
            'id' => 'download_action',
            'labelClass' => 'g-input icon-download',
            'itemClass' => 'item',
            'label' => '{LNG_When download}',
            'options' => Language::get('DOWNLOAD_ACTIONS'),
            'value' => $index->download_action
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Display}'
        ));
        // list_per_page
        $fieldset->add('select', array(
            'id' => 'list_per_page',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Number}',
            'comment' => '{LNG_The number of items displayed per page}',
            'options' => array(10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50),
            'value' => isset($index->list_per_page) ? $index->list_per_page : 10
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Role of Members}'
        ));
        // สถานะสมาชิก
        $table = new HtmlTable(array(
            'class' => 'responsive horiz-table border data'
        ));
        $table->addHeader(array(
            array(),
            array('text' => '{LNG_Upload}'),
            array('text' => '{LNG_Moderator}'),
            array('text' => '{LNG_Settings}')
        ));
        foreach (self::$cfg->member_status as $i => $item) {
            if ($i != 1) {
                $row = array();
                $row[] = array(
                    'scope' => 'col',
                    'text' => $item
                );
                $check = isset($index->can_upload) && is_array($index->can_upload) && in_array($i, $index->can_upload) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => '<label data-text="{LNG_Upload}"><input type=checkbox name=can_upload[] title="{LNG_Members of this group can upload file}" value='.$i.$check.'></label>'
                );
                $check = isset($index->moderator) && is_array($index->moderator) && in_array($i, $index->moderator) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => $i > 1 ? '<label data-text="{LNG_Moderator}"><input type=checkbox name=moderator[] title="{LNG_Members of this group can edit, delete items created by others}" value='.$i.$check.'></label>' : ''
                );
                $check = isset($index->can_config) && is_array($index->can_config) && in_array($i, $index->can_config) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => $i > 1 ? '<label data-text="{LNG_Settings}"><input type=checkbox name=can_config[] title="{LNG_Members of this group can setting the module (not recommend)}" value='.$i.$check.'></label>' : ''
                );
                $table->addRow($row, array(
                    'class' => 'status'.$i
                ));
            }
        }
        $div = $fieldset->add('div', array(
            'class' => 'item'
        ));
        $div->appendChild($table->render());
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
            'name' => 'id',
            'value' => $index->module_id
        ));
        Gcms::$view->setContentsAfter(array(
            '/:upload_max_filesize/' => ini_get('upload_max_filesize')
        ));
        return $form->render();
    }
}
