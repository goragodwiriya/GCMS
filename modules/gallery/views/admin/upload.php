<?php
/**
 * @filesource modules/gallery/views/admin/upload.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Upload;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * ฟอร์มอัปโหลดรูปภาพ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ฟอร์มอัปโหลดรูปภาพ
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
            'id' => 'gallery_upload',
            'class' => 'setup_frm gupload_frm',
            'action' => 'index.php/gallery/model/admin/upload/submit',
            'ajax' => false
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Upload or Delete pictures in} '.$index->topic
        ));
        // fileupload
        $fieldset->add('file', array(
            'id' => 'fileupload',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-upload',
            'label' => '{LNG_Browse file}',
            'comment' => '{LNG_Upload a photo no larger than :width pixels :type types only larger than a specified size will be resize automatically}',
            'accept' => $index->img_typies
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // btnCancel
        $fieldset->add('button', array(
            'id' => 'btnCancel',
            'class' => 'button orange large',
            'value' => '{LNG_Cancel upload}',
            'disabled' => true
        ));
        // selectAll
        $fieldset->add('button', array(
            'id' => 'selectAll',
            'class' => 'button blue large',
            'value' => '{LNG_Select all}'
        ));
        // clearSelected
        $fieldset->add('button', array(
            'id' => 'clearSelected',
            'class' => 'button pink large',
            'value' => '{LNG_Clear selected}'
        ));
        // btnDelete
        $fieldset->add('button', array(
            'id' => 'btnDelete',
            'class' => 'button red large',
            'value' => '{LNG_Delete}'
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'album_id',
            'value' => $index->id
        ));
        // module_id
        $fieldset->add('hidden', array(
            'id' => 'module_id',
            'value' => $index->module_id
        ));
        Gcms::$view->setContentsAfter(array(
            '/:type/' => implode(', ', $index->img_typies),
            '/:width/' => $index->image_width
        ));
        $form->add('fieldset', array(
            'id' => 'fsUploadProgress'
        ));
        $tb_upload = $form->add('fieldset', array(
            'id' => 'tb_upload',
            'class' => 'tb_upload'
        ));
        foreach (\Gallery\Admin\Write\Model::pictures($index) as $i => $item) {
            $figure = $tb_upload->add('figure', array(
                'id' => 'L_'.$item->id,
                'class' => 'item icon-drag sort',
                'style' => 'background-image:url('.WEB_URL.DATA_FOLDER.'gallery/'.$index->id.'/thumb_'.$item->image.');'
            ));
            $figure->add('a', array(
                'id' => 'delete_'.$item->id.'_'.$index->id,
                'class' => 'icon-uncheck',
                'title' => '{LNG_Delete}'
            ));
        }
        $form->script('initGUploads("gallery_upload", '.$index->id.', "gallery/model/admin/setup/action", '.$index->module_id.')');
        return $form->render();
    }
}
