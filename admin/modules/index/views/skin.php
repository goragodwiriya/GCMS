<?php
/**
 * @filesource modules/index/views/skin.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Skin;

use Kotchasan\Html;

/**
 * ตั้งค่า template
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * module=skin
     *
     * @param object $config
     *
     * @return string
     */
    public function render($config)
    {
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/skin/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Website display settings}'
        ));
        // logo
        if (isset($config->logo) && is_file(ROOT_PATH.DATA_FOLDER.'image/'.$config->logo)) {
            $img = $config->logo == 'logo.swf' ? WEB_URL.'admin/skin/'.self::$cfg->skin.'/img/swf.png' : WEB_URL.DATA_FOLDER.'image/'.$config->logo;
        } else {
            $img = WEB_URL.'skin/img/blank.gif';
        }
        $fieldset->add('file', array(
            'id' => 'logo',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Logo}',
            'comment' => '{LNG_Images or flash files on the website header (header) accepted jpg gif png files and swf}',
            'dataPreview' => 'logoImage',
            'previewSrc' => $img
        ));
        // delete_logo
        $fieldset->add('checkbox', array(
            'id' => 'delete_logo',
            'itemClass' => 'subitem',
            'label' => '{LNG_Remove} {LNG_Logo}',
            'value' => 1
        ));
        // bg_image
        $img = isset($config->bg_image) && is_file(ROOT_PATH.DATA_FOLDER.'image/'.$config->bg_image) ? WEB_URL.DATA_FOLDER.'image/'.$config->bg_image : WEB_URL.'skin/img/blank.gif';
        $fieldset->add('file', array(
            'id' => 'bg_image',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Background image}',
            'comment' => '{LNG_Background picture of the site (body)}',
            'dataPreview' => 'bgImage',
            'previewSrc' => $img
        ));
        // delete_bg_image
        $fieldset->add('checkbox', array(
            'id' => 'delete_bg_image',
            'itemClass' => 'subitem',
            'label' => '{LNG_Remove} {LNG_Background image}',
            'value' => 1
        ));
        // bg_color
        $fieldset->add('color', array(
            'id' => 'bg_color',
            'labelClass' => 'g-input icon-color',
            'itemClass' => 'item',
            'label' => '{LNG_Background color}',
            'comment' => '{LNG_Background color of the site (body), eg #FFFFFF}',
            'value' => isset($config->bg_color) ? $config->bg_color : ''
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        return $form->render();
    }
}
