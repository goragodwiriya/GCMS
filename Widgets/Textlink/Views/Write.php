<?php
/**
 * @filesource Widgets/Textlink/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlink\Views;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Write extends \Gcms\Adminview
{
    /**
     * module=Textlink-Write
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
            'action' => 'index.php/Widgets/Textlink/Models/Write/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Config}'
        ));
        // name
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Name}',
            'for' => 'name',
            'comment' => '{LNG_Enter the name of Textlink english lowercase letters and numbers. Used for grouping similar position.}'
        ));
        $groups->add('text', array(
            'id' => 'name',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'width',
            'maxlength' => 11,
            'value' => $index->name
        ));
        $groups->add('em', array(
            'id' => 'name_demo',
            'class' => 'width',
            'innerHTML' => '{WIDGET_TEXTLINK}'
        ));
        // description
        $fieldset->add('text', array(
            'id' => 'description',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Description}',
            'comment' => '{LNG_Notes or short description of the link}',
            'maxlength' => 49,
            'value' => $index->description
        ));
        // type
        $lng = Language::get('TEXTLINK_TYPIES');
        $styles = array();
        foreach (include (ROOT_PATH.'Widgets/Textlink/styles.php') as $key => $value) {
            $styles[$key] = isset($lng[$key]) ? $lng[$key] : '-';
        }
        $fieldset->add('select', array(
            'id' => 'type',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Type}',
            'comment' => '{LNG_Select the type of link you want, or select the top entry . If you want to link this on their own , such as Adsense.}',
            'options' => $styles,
            'value' => $index->type
        ));
        // template
        $fieldset->add('textarea', array(
            'id' => 'template',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Template}',
            'comment' => '{LNG_Fill HTML code for this link You can choose to enter the code from another source , such as Adsense code or description of the links below.}',
            'rows' => 5,
            'placeholder' => '<HTML>'
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Detail}'
        ));
        // text
        $fieldset->add('text', array(
            'id' => 'text',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'label' => '{LNG_Text}',
            'comment' => '{LNG_Message on the link can be used to force &lt;br&gt; a new text line}',
            'value' => $index->text
        ));
        // url
        $fieldset->add('text', array(
            'id' => 'url',
            'labelClass' => 'g-input icon-world',
            'itemClass' => 'item',
            'label' => '{LNG_URL}',
            'comment' => '{LNG_Links for this item, which will open this page when click on it}',
            'value' => str_replace(array('{', '}'), array('&#x007B;', '&#x007D;'), $index->url)
        ));
        // target
        $fieldset->add('select', array(
            'id' => 'target',
            'labelClass' => 'g-input icon-forward',
            'itemClass' => 'item',
            'label' => '{LNG_The opening page of links}',
            'comment' => '{LNG_Determine how to turn the page when a link is clicked}',
            'options' => Language::get('MENU_TARGET'),
            'value' => $index->target
        ));
        // logo
        if (!empty($index->logo) && is_file(ROOT_PATH.DATA_FOLDER.'image/'.$index->logo)) {
            $img = WEB_URL.DATA_FOLDER.'image/'.$index->logo;
        } else {
            $img = WEB_URL.'skin/img/blank.gif';
        }
        $fieldset->add('file', array(
            'id' => 'logo',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Image}',
            'comment' => '{LNG_Upload an image for the link (If you have). Type jpg, gif, png only, uploaded the image should be the same size.}',
            'dataPreview' => 'imgLogo',
            'previewSrc' => $img
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Publish this item}'
        ));
        // publish_start
        $fieldset->add('date', array(
            'id' => 'publish_start',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Published date}',
            'value' => date('Y-m-d', $index->publish_start)
        ));
        // publish_end
        $groups = $fieldset->add('groups-table', array(
            'label' => '{LNG_Published close}',
            'for' => 'publish_end',
            'comment' => '{LNG_The date of the start and end of the link. (Links are performed within a given time automatically.)}'
        ));
        $groups->add('date', array(
            'id' => 'publish_end',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width',
            'disabled' => $index->publish_end == 0,
            'value' => date('Y-m-d', $index->publish_end)
        ));
        $groups->add('checkbox', array(
            'id' => 'dateless',
            'itemClass' => 'width',
            'label' => '{LNG_Dateless}',
            'checked' => $index->publish_end == 0,
            'value' => 1
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
        // Javascript
        $form->script('initTextlinkWrite();');
        // คืนค่า HTML
        return $form->render();
    }
}
