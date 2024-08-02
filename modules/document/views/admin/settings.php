<?php
/**
 * @filesource modules/document/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Settings;

use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=document-settings
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
            'action' => 'index.php/document/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Thumbnail}'
        ]);
        $groups = $fieldset->add('groups', [
            'label' => '{LNG_Size of the icons}',
            'comment' => '{LNG_Size of the image at pixels (Images should be at least 696 pixels wide)}'
        ]);
        // icon_width
        $groups->add('number', [
            'id' => 'icon_width',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Width}',
            'value' => $index->icon_width
        ]);
        // icon_height
        $groups->add('number', [
            'id' => 'icon_height',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'width',
            'label' => '{LNG_Height}',
            'value' => $index->icon_height
        ]);
        // img_typies
        $fieldset->add('checkboxgroups', [
            'id' => 'img_typies',
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Types of files that can be uploaded} ({LNG_must choose at least one item})',
            'labelClass' => 'g-input icon-thumbnail',
            'options' => ['jpg' => 'jpg', 'jpeg' => 'jpeg', 'gif' => 'gif', 'png' => 'png'],
            'value' => $index->img_typies
        ]);
        // default_icon
        $fieldset->add('file', [
            'id' => 'default_icon',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'comment' => '{LNG_Upload icons (default) as defined above. Can be used as thumbnail if no thumbnail of story. (Resized automatically, if you want to use animated images or images transparent Please be prepared to fit the image size set.)}',
            'dataPreview' => 'iconImage',
            'previewSrc' => WEB_URL.$index->default_icon
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Default Settings}'
        ]);
        // published
        $fieldset->add('select', [
            'id' => 'published',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Published}',
            'comment' => '{LNG_If you choose to unpublish contributions will not be displayed on the page immediately. The Admin can review and published it later.}',
            'options' => Language::get('PUBLISHEDS'),
            'value' => $index->published
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Display}'
        ]);
        $groups = $fieldset->add('groups', [
            'comment' => '{LNG_The number of items displayed per page}'
        ]);
        // จำนวนคอลัมน์
        $cols = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 8 => 8];
        // cols
        $groups->add('select', [
            'id' => 'cols',
            'labelClass' => 'g-input icon-cols',
            'itemClass' => 'width50',
            'label' => '{LNG_Cols}',
            'options' => $cols,
            'value' => $index->cols
        ]);
        // rows
        $groups->add('number', [
            'id' => 'rows',
            'labelClass' => 'g-input icon-rows',
            'itemClass' => 'width50',
            'label' => '{LNG_Rows}',
            'value' => $index->rows
        ]);
        // sort
        $sorts = ['{LNG_Last updated}', '{LNG_Article Date}', '{LNG_Published date}', 'ID', '{LNG_Random}'];
        $fieldset->add('select', [
            'id' => 'sort',
            'labelClass' => 'g-input icon-sort-asc',
            'itemClass' => 'item',
            'label' => '{LNG_Sort}',
            'comment' => '{LNG_Determine how to sort the items displayed}',
            'options' => $sorts,
            'value' => $index->sort
        ]);
        // new_date
        $options = [];
        for ($i = 0; $i < 31; ++$i) {
            $options[$i] = $i.' {LNG_days}';
        }
        $fieldset->add('select', [
            'id' => 'new_date',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'item',
            'label' => '{LNG_New mark}',
            'comment' => '{LNG_Setting the number of days an item will show up as New} ({LNG_0 to disable})',
            'options' => $options,
            'value' => $index->new_date / 86400
        ]);
        // viewing
        $fieldset->add('select', [
            'id' => 'viewing',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Viewing}',
            'comment' => '{LNG_Determine how to view the content for the page is reserved for members only}',
            'options' => Language::get('MEMBER_ONLY_LIST'),
            'value' => $index->viewing
        ]);
        $groups = $fieldset->add('groups', [
            'comment' => '{LNG_Set the Display category list. If you choose to disable. System will jump to display the list of articles.}'
        ]);
        // category_display
        $groups->add('select', [
            'id' => 'category_display',
            'labelClass' => 'g-input icon-category',
            'itemClass' => 'width50',
            'label' => '{LNG_Display Category}',
            'options' => Language::get('BOOLEANS'),
            'value' => $index->category_display
        ]);
        // category_cols
        $groups->add('select', [
            'id' => 'category_cols',
            'labelClass' => 'g-input icon-cols',
            'itemClass' => 'width50',
            'label' => '{LNG_Cols}',
            'options' => $cols,
            'value' => $index->category_cols
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Display in the widget}'
        ]);
        // news_count
        $fieldset->add('number', [
            'id' => 'news_count',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Number}',
            'comment' => '{LNG_Set the number of entries displayed (0 means not shown)}',
            'value' => $index->news_count
        ]);
        // news_sort
        $fieldset->add('select', [
            'id' => 'news_sort',
            'labelClass' => 'g-input icon-sort-asc',
            'itemClass' => 'item',
            'label' => '{LNG_Sort}',
            'comment' => '{LNG_Determine how to sort the items displayed}',
            'options' => $sorts,
            'value' => $index->news_sort
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Setting the display by date or by tags}'
        ]);
        $groups = $fieldset->add('groups', [
            'comment' => '{LNG_The number of items displayed per page}'
        ]);
        // document_cols
        $groups->add('select', [
            'id' => 'document_cols',
            'labelClass' => 'g-input icon-cols',
            'itemClass' => 'width50',
            'label' => '{LNG_Cols}',
            'options' => $cols,
            'value' => self::$cfg->document_cols
        ]);
        // document_rows
        $groups->add('number', [
            'id' => 'document_rows',
            'labelClass' => 'g-input icon-rows',
            'itemClass' => 'width50',
            'label' => '{LNG_Rows}',
            'value' => self::$cfg->document_rows
        ]);
        // Thumbnail สำหรับบทความตามวันที่หรือตามเรื่อง
        $document_icon = is_file(ROOT_PATH.DATA_FOLDER.'document/default_icon.png') ? WEB_URL.DATA_FOLDER.'document/default_icon.png' : WEB_URL.$index->default_icon;
        $fieldset->add('file', [
            'id' => 'document_icon',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'comment' => '{LNG_Upload icons (default) as defined above. Can be used as thumbnail if no thumbnail of story. (Resized automatically, if you want to use animated images or images transparent Please be prepared to fit the image size set.)}',
            'dataPreview' => 'documentIcon',
            'previewSrc' => $document_icon
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Notifications}'
        ]);
        // line_notifications
        $fieldset->add('checkboxgroups', [
            'id' => 'line_notifications',
            'labelClass' => 'g-input icon-comments',
            'itemClass' => 'item',
            'label' => '{LNG_Send a message to the Line when}',
            'options' => Language::get('DOCUMENT_NOTIFICATIONS'),
            'value' => isset($index->line_notifications) ? $index->line_notifications : []
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
            ['text' => '{LNG_Comment}'],
            ['text' => '{LNG_Viewing}'],
            ['text' => '{LNG_Writing}'],
            ['text' => '{LNG_Moderator}'],
            ['text' => '{LNG_Settings}']
        ]);
        foreach ([-1 => '{LNG_Guest}'] + self::$cfg->member_status as $i => $item) {
            $row = [];
            $row[] = [
                'scope' => 'col',
                'text' => $item
            ];
            $check = in_array($i, $index->can_reply) ? ' checked' : '';
            $row[] = [
                'class' => 'center',
                'text' => '<label data-text="{LNG_Comment}"><input type=checkbox name=can_reply[] title="{LNG_Members of this group can post comment}" value='.$i.$check.'></label>'
            ];
            $check = isset($index->can_view) && is_array($index->can_view) && in_array($i, $index->can_view) ? ' checked' : '';
            $row[] = [
                'class' => 'center',
                'text' => $i == 1 ? '' : '<label data-text="{LNG_Viewing}"><input type=checkbox name=can_view[] title="{LNG_Members of this group can see the content}" value='.$i.$check.'></label>'
            ];
            $check = isset($index->can_write) && is_array($index->can_write) && in_array($i, $index->can_write) ? ' checked' : '';
            $row[] = [
                'class' => 'center',
                'text' => $i > -1 && $i != 1 ? '<label data-text="{LNG_Writing}"><input type=checkbox name=can_write[] title="{LNG_Members of this group can create or edit}" value='.$i.$check.'></label>' : ''
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
        return $form->render();
    }
}
