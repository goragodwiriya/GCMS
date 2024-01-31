<?php
/**
 * @filesource modules/board/views/admin/categorywrite.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Admin\Categorywrite;

use Gcms\Gcms;
use Kotchasan\ArrayTool;
use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;

/**
 * module=board-categorywrite
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มสร้าง/แก้ไข หมวดหมู่
     *
     * @param object $index
     *
     * @return string
     */
    public function render($index)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/board/model/admin/categorywrite/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Category}'
        ));
        // category_id
        $fieldset->add('text', array(
            'id' => 'category_id',
            'labelClass' => 'g-input icon-category',
            'itemClass' => 'item',
            'label' => '{LNG_ID}',
            'comment' => '{LNG_The ID of the category, is unique to each category and must be greater than 0.}',
            'value' => $index->category_id
        ));
        // ภาษาปัจจุบัน
        $lng = Language::name();
        // ภาษาที่ติดตั้ง
        $languages = Gcms::installedLanguage();
        $multi_language = count($languages) > 1;
        // topic,detail,icon
        $topic = ArrayTool::unserialize($index->topic);
        $detail = ArrayTool::unserialize($index->detail);
        $icon = ArrayTool::unserialize($index->icon);
        foreach ($languages as $item) {
            $fieldset = $form->add('fieldset', array(
                'title' => '{LNG_Details of} {LNG_Category} <img src="'.WEB_URL.'language/'.$item.'.gif" alt="'.$item.'">'
            ));
            // topic
            $fieldset->add('text', array(
                'id' => 'topic_'.$item,
                'name' => 'topic['.$item.']',
                'labelClass' => 'g-input icon-edit',
                'itemClass' => 'item',
                'label' => '{LNG_Category}',
                'comment' => '{LNG_The name of the category, less than 50 characters}',
                'maxlength' => 50,
                'value' => isset($topic[$item]) ? $topic[$item] : (isset($topic['']) && (!$multi_language || ($item == $lng && !isset($topic[$lng]))) ? $topic[''] : '')
            ));
            // detail
            $fieldset->add('textarea', array(
                'id' => 'detail_'.$item,
                'name' => 'detail['.$item.']',
                'labelClass' => 'g-input icon-file',
                'itemClass' => 'item',
                'label' => '{LNG_Description}',
                'comment' => '{LNG_Description of this category, less than 255 characters}',
                'rows' => 3,
                'value' => isset($detail[$item]) ? $detail[$item] : (isset($detail['']) && (!$multi_language || ($item == $lng && !isset($detail[$lng]))) ? $detail[''] : '')
            ));
            // icon
            $img = isset($icon[$item]) ? $icon[$item] : (isset($icon['']) && (!$multi_language || ($item == $lng && !isset($icon[$lng]))) ? $icon[''] : '');
            if (is_file(ROOT_PATH.DATA_FOLDER.'board/'.$img)) {
                $img = WEB_URL.DATA_FOLDER.'board/'.$img;
            } else {
                $img = WEB_URL.(isset($index->default_icon) ? $index->default_icon : 'modules/board/img/default_icon.png');
            }
            $fieldset->add('file', array(
                'id' => 'icon_'.$item,
                'name' => 'icon['.$item.']',
                'labelClass' => 'g-input icon-upload',
                'itemClass' => 'item',
                'label' => '{LNG_Icon}',
                'comment' => '{LNG_Image upload types :type only, should be prepared to have the same size}',
                'dataPreview' => 'img'.$item,
                'previewSrc' => $img
            ));
        }
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
            'labelClass' => 'g-input icon-config',
            'itemClass' => 'item',
            'label' => '{LNG_Upload rules}',
            'comment' => '{LNG_The rules for uploading pictures for questions. (Choose the type of files. If is uploaded.)}',
            'options' => Language::get('IMG_LAW'),
            'value' => isset($index->img_law) ? $index->img_law : 0
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Role of Members}'
        ));
        // สถานะสมาชิก
        $status = array();
        $status[-1] = '{LNG_Guest}';
        foreach (self::$cfg->member_status as $i => $item) {
            $status[$i] = $item;
        }
        $table = new HtmlTable(array(
            'class' => 'responsive horiz-table border data'
        ));
        $table->addHeader(array(
            array(),
            array('text' => '{LNG_Posting}'),
            array('text' => '{LNG_Comment}'),
            array('text' => '{LNG_Viewing}'),
            array('text' => '{LNG_Moderator}')
        ));
        foreach ($status as $i => $item) {
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
                $check = in_array($i, $index->can_reply) ? ' checked' : '';
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
                    'text' => $i > 1 ? '<label data-text="{LNG_Moderator"><input type=checkbox name=moderator[] title="{LNG_Members of this group can edit, delete items created by others}" value='.$i.$check.'></label>' : ''
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
            'id' => 'id',
            'value' => $index->id
        ));
        // module_id
        $fieldset->add('hidden', array(
            'id' => 'module_id',
            'value' => $index->module_id
        ));
        Gcms::$view->setContentsAfter(array(
            '/:type/' => 'jpg, jpeg, gif, png'
        ));
        return $form->render();
    }
}
