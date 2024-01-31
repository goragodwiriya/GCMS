<?php
/**
 * @filesource modules/board/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Admin\Settings;

use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;

/**
 * module=board-settings
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
            'action' => 'index.php/board/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Thumbnail}'
        ));
        $groups = $fieldset->add('groups', array(
            'label' => '{LNG_Size of the icons}',
            'comment' => '{LNG_Size of the image at pixels (Images should be at least 696 pixels wide)}'
        ));
        // icon_width
        $groups->add('number', array(
            'id' => 'icon_width',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Width}',
            'value' => $index->icon_width
        ));
        // icon_height
        $groups->add('number', array(
            'id' => 'icon_height',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'width',
            'label' => '{LNG_Height}',
            'value' => $index->icon_height
        ));
        // img_typies
        $fieldset->add('checkboxgroups', array(
            'id' => 'img_typies',
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Types of files that can be uploaded} ({LNG_must choose at least one item})',
            'labelClass' => 'g-input icon-thumbnail',
            'options' => array('jpg' => 'jpg', 'jpeg' => 'jpeg', 'gif' => 'gif', 'png' => 'png'),
            'value' => $index->img_typies
        ));
        // default_icon
        $fieldset->add('file', array(
            'id' => 'default_icon',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Browse file}',
            'comment' => '{LNG_Upload icons (default) as defined above. Can be used as thumbnail if no thumbnail of story. (Resized automatically, if you want to use animated images or images transparent Please be prepared to fit the image size set.)}',
            'dataPreview' => 'iconImage',
            'previewSrc' => WEB_URL.$index->default_icon
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Display}'
        ));
        // list_per_page
        $fieldset->add('number', array(
            'id' => 'list_per_page',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Number}',
            'comment' => '{LNG_Set the number of entries displayed}',
            'value' => $index->list_per_page
        ));
        // new_date
        $options = array();
        for ($i = 0; $i < 31; ++$i) {
            $options[$i] = $i.' {LNG_days}';
        }
        $fieldset->add('select', array(
            'id' => 'new_date',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'item',
            'label' => '{LNG_New mark}',
            'comment' => '{LNG_Setting the number of days an item will show up as New} ({LNG_0 to disable})',
            'options' => $options,
            'value' => $index->new_date / 86400
        ));
        // viewing
        $fieldset->add('select', array(
            'id' => 'viewing',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Viewing}',
            'comment' => '{LNG_Determine how to view the content for the page is reserved for members only}',
            'options' => Language::get('MEMBER_ONLY_LIST'),
            'value' => $index->viewing
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_Set the Display category list. If you choose to disable. System will jump to display the list of articles.}'
        ));
        // category_display
        $groups->add('select', array(
            'id' => 'category_display',
            'labelClass' => 'g-input icon-category',
            'itemClass' => 'width50',
            'label' => '{LNG_Display Category}',
            'options' => Language::get('BOOLEANS'),
            'value' => $index->category_display
        ));
        // category_cols
        $groups->add('select', array(
            'id' => 'category_cols',
            'labelClass' => 'g-input icon-cols',
            'itemClass' => 'width50',
            'label' => '{LNG_Cols}',
            'options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 8 => 8),
            'value' => $index->category_cols
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Display in the widget}'
        ));
        // news_count
        $fieldset->add('number', array(
            'id' => 'news_count',
            'labelClass' => 'g-input icon-published1',
            'itemClass' => 'item',
            'label' => '{LNG_Number}',
            'comment' => '{LNG_Set the number of entries displayed (0 means not shown)}',
            'value' => $index->news_count
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Upload}'
        ));
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Type of files allowed to upload it, if not select any item can not be uploaded.}'
        ));
        // img_upload_type
        foreach (array('jpg', 'jpeg', 'gif', 'png') as $item) {
            $groups->add('checkbox', array(
                'id' => 'img_upload_type_'.$item,
                'name' => 'img_upload_type[]',
                'itemClass' => 'width',
                'label' => $item,
                'value' => $item,
                'checked' => isset($index->img_upload_type) && is_array($index->img_upload_type) ? in_array($item, $index->img_upload_type) : false
            ));
        }
        // img_upload_size
        $upload_max_filesize = UploadedFile::getUploadSize(true);
        $options = array();
        foreach (array(100, 200, 300, 400, 500, 600, 700, 800, 900, 1024, 2048) as $item) {
            if ($item * 1024 <= $upload_max_filesize) {
                $options[$item] = $item.' Kb.';
            }
        }
        $fieldset->add('select', array(
            'id' => 'img_upload_size',
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Size of the file upload}',
            'comment' => '{LNG_Size of file allowed to upload up (Kb.)}',
            'options' => $options,
            'value' => isset($index->img_upload_size) ? $index->img_upload_size : $upload_max_filesize / 1024
        ));
        // img_law
        $fieldset->add('select', array(
            'id' => 'img_law',
            'labelClass' => 'g-input icon-image',
            'itemClass' => 'item',
            'label' => '{LNG_Upload rules}',
            'comment' => '{LNG_The rules for uploading pictures for questions. (Choose the type of files. If is uploaded.)}',
            'options' => Language::get('IMG_LAW'),
            'value' => isset($index->img_law) ? $index->img_law : 0
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Notifications}'
        ));
        // line_notifications
        $fieldset->add('checkboxgroups', array(
            'id' => 'line_notifications',
            'labelClass' => 'g-input icon-comments',
            'itemClass' => 'item',
            'label' => '{LNG_Send a message to the Line when}',
            'options' => Language::get('BOARD_NOTIFICATIONS'),
            'value' => isset($index->line_notifications) ? $index->line_notifications : array()
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
            array('text' => '{LNG_Posting}'),
            array('text' => '{LNG_Comment}'),
            array('text' => '{LNG_Viewing}'),
            array('text' => '{LNG_Moderator}'),
            array('text' => '{LNG_Settings}')
        ));
        foreach (array(-1 => '{LNG_Guest}') + self::$cfg->member_status as $i => $item) {
            if ($i != 1) {
                $row = array();
                $row[] = array(
                    'scope' => 'col',
                    'text' => $item
                );
                $check = isset($index->can_post) && is_array($index->can_post) && in_array($i, $index->can_post) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => '<label data-text="{LNG_Posting}"><input type=checkbox name=can_post[] title="{LNG_Members of this group can post}" value='.$i.$check.'></label>'
                );
                $check = isset($index->can_reply) && in_array($i, $index->can_reply) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => '<label data-text="{LNG_Comment}"><input type=checkbox name=can_reply[] title="{LNG_Members of this group can post comment}" value='.$i.$check.'></label>'
                );
                $check = isset($index->can_view) && is_array($index->can_view) && in_array($i, $index->can_view) ? ' checked' : '';
                $row[] = array(
                    'class' => 'center',
                    'text' => '<label data-text="{LNG_Viewing}"><input type=checkbox name=can_view[] title="{LNG_Members of this group can see the content}" value='.$i.$check.'></label>'
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
        // คืนค่า HTML
        return $form->render();
    }
}
