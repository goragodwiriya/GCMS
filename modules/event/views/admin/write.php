<?php
/**
 * @filesource event/views/admin/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Admin\Write;

use Kotchasan\Date;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=event-write
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มสร้าง/แก้ไข Event
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
            'action' => 'index.php/event/model/admin/write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of} {LNG_Event}'
        ));
        // topic
        $fieldset->add('text', array(
            'id' => 'topic',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Topic}',
            'comment' => '{LNG_Title or topic 3 to 255 characters}',
            'maxlength' => 255,
            'value' => isset($index->topic) ? $index->topic : ''
        ));
        // color
        $fieldset->add('color', array(
            'id' => 'color',
            'labelClass' => 'g-input icon-color',
            'itemClass' => 'item',
            'label' => '{LNG_Color of Event}',
            'comment' => '{LNG_Choose colors that are displayed in the Event calendar}',
            'value' => isset($index->color) ? $index->color : ''
        ));
        $group = $fieldset->add('groups-table', array(
            'comment' => '{LNG_Specify the start and end of the event}',
            'label' => '{LNG_Date and time of event}'
        ));
        $row = $group->add('row');
        // begin_date
        $begin_date = isset($index->begin_date) ? strtotime($index->begin_date) : time();
        $row->add('date', array(
            'id' => 'begin_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width',
            'value' => date('Y-m-d', $begin_date)
        ));
        $row->add('time', array(
            'id' => 'begin_time',
            'itemClass' => 'width',
            'label' => '{LNG_from time}&nbsp;',
            'value' => date('H:i', $begin_date)
        ));
        $end_date = isset($index->end_date) ? strtotime($index->end_date) : time();
        $row->add('time', array(
            'id' => 'to_time',
            'itemClass' => 'width',
            'label' => '{LNG_to time}&nbsp;',
            'value' => date('H:i', $end_date)
        ));
        $row->add('checkbox', array(
            'id' => 'forever',
            'label' => '{LNG_forever}',
            'itemClass' => 'width',
            'checked' => !isset($index->end_date) || $index->end_date == '0000-00-00 00:00:00',
            'value' => 1
        ));
        // keywords
        $fieldset->add('textarea', array(
            'id' => 'keywords',
            'labelClass' => 'g-input icon-tags',
            'itemClass' => 'item',
            'label' => '{LNG_Keywords}',
            'comment' => '{LNG_Text keywords for SEO or Search Engine to search}',
            'value' => isset($index->keywords) ? $index->keywords : ''
        ));
        // description
        $fieldset->add('textarea', array(
            'id' => 'description',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Description}',
            'comment' => '{LNG_Text short summary of your story. Which can be used to show in your theme.} ({LNG_If not, Program will fill in the contents of the first paragraph})',
            'value' => isset($index->description) ? $index->description : ''
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
        // published_date
        $fieldset->add('date', array(
            'id' => 'published_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Published date}',
            'comment' => '{LNG_The date of publication of this information. The publisher will start automatically when you log on due date}',
            'value' => isset($index->published_date) ? $index->published_date : date('Y-m-d')
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
        $form->script('initEvent();');
        return $form->render();
    }
}
