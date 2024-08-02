<?php
/**
 * @filesource modules/download/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Admin\Settings;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;
use Kotchasan\Text;

/**
 * module=download-settings
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
        $form = Html::create('form', [
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/download/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Upload}'
        ]);
        // file_typies
        $fieldset->add('text', [
            'id' => 'file_typies',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Specify the file extension that allows uploading. English lowercase letters and numbers 2-4 characters to separate each type with a comma (,) and without spaces. eg zip,rar,doc,docx}',
            'value' => implode(',', $index->file_typies)
        ]);
        // upload_size
        $sizes = [];
        foreach ([2, 4, 6, 8, 16, 32, 64, 128, 256, 512, 1024, 2048] as $i) {
            $a = $i * 1048576;
            $sizes[$a] = Text::formatFileSize($a);
        }
        $fieldset->add('select', [
            'id' => 'upload_size',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Size of the file upload}',
            'comment' => '{LNG_The size of the files can be uploaded. (Should not exceed the value of the Server :upload_max_filesize.)}',
            'options' => $sizes,
            'value' => $index->upload_size
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Display}'
        ]);
        // list_per_page
        $fieldset->add('select', [
            'id' => 'list_per_page',
            'labelClass' => 'g-input icon-rows',
            'itemClass' => 'item',
            'label' => '{LNG_Number}',
            'comment' => '{LNG_The number of items displayed per page}',
            'options' => [10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50],
            'value' => $index->list_per_page
        ]);
        // sort
        $sorts = ['ID', '{LNG_Last updated}', '{LNG_Random}'];
        $fieldset->add('select', [
            'id' => 'sort',
            'labelClass' => 'g-input icon-sort-asc',
            'itemClass' => 'item',
            'label' => '{LNG_Sort}',
            'comment' => '{LNG_Determine how to sort the items displayed}',
            'options' => $sorts,
            'value' => $index->sort
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Role of Members}'
        ]);
        // สถานะสมาชิก
        $table = new HtmlTable([
            'class' => 'responsive horiz-table border data'
        ]);
        $table->addHeader([
            [],
            ['text' => '{LNG_Download}'],
            ['text' => '{LNG_Upload}'],
            ['text' => '{LNG_Moderator}'],
            ['text' => '{LNG_Settings}']
        ]);
        foreach ([-1 => '{LNG_Guest}'] + self::$cfg->member_status as $i => $item) {
            $row = [];
            $row[] = [
                'scope' => 'col',
                'text' => $item
            ];
            $check = isset($index->can_download) && is_array($index->can_download) && in_array($i, $index->can_download) ? ' checked' : '';
            $row[] = [
                'class' => 'center',
                'text' => '<label data-text="{LNG_Download}"><input type=checkbox name=can_download[] title="{LNG_Members of this group can download file}" value='.$i.$check.'></label>'
            ];
            $check = isset($index->can_upload) && is_array($index->can_upload) && in_array($i, $index->can_upload) ? ' checked' : '';
            $row[] = [
                'class' => 'center',
                'text' => $i > 1 ? '<label data-text="{LNG_Upload}"><input type=checkbox name=can_upload[] title="{LNG_Members of this group can upload file}" value='.$i.$check.'></label>' : ''
            ];
            $check = isset($index->moderator) && is_array($index->moderator) && in_array($i, $index->moderator) ? ' checked' : '';
            $row[] = [
                'class' => 'center',
                'text' => $i > 1 ? '<label data-text="{LNG_Moderator}"><input type=checkbox name=moderator[] title="{LNG_Members of this group can edit, delete items created by others}" value='.$i.$check.'></label>' : ''
            ];
            $check = isset($index->can_config) && is_array($index->can_config) && in_array($i, $index->can_config) ? ' checked' : '';
            $row[] = [
                'class' => 'center',
                'text' => $i > 1 ? '<label data-text="{LNG_Settings}"><input type=checkbox name=can_config[] title="{LNG_Members of this group can setting the module (not recommend)}" value='.$i.$check.'></label>' : ''
            ];
            $table->addRow($row, [
                'class' => 'status'.$i
            ]);
        }
        $div = $fieldset->add('div', [
            'class' => 'item'
        ]);
        $div->appendChild($table->render());
        $fieldset = $form->add('fieldset', [
            'class' => 'submit'
        ]);
        // submit
        $fieldset->add('submit', [
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ]);
        // id
        $fieldset->add('hidden', [
            'name' => 'id',
            'value' => $index->module_id
        ]);
        Gcms::$view->setContentsAfter([
            '/:upload_max_filesize/' => ini_get('upload_max_filesize')
        ]);
        return $form->render();
    }
}
