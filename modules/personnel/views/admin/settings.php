<?php
/**
 * @filesource modules/personnel/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Admin\Settings;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;

/**
 * module=personnel-settings
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
            'action' => 'index.php/personnel/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Personner image}'
        ]);
        $groups = $fieldset->add('groups', [
            'label' => '{LNG_Size of} {LNG_Image}',
            'comment' => '{LNG_The size of the images are stored as pixels. The image will be resized automatically.}'
        ]);
        // image_width
        $groups->add('text', [
            'id' => 'image_width',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Width}',
            'value' => $index->image_width
        ]);
        // image_height
        $groups->add('text', [
            'id' => 'image_height',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'width',
            'label' => '{LNG_Height}',
            'value' => $index->image_height
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
            ['text' => '{LNG_Writing}'],
            ['text' => '{LNG_Settings}']
        ]);
        foreach (self::$cfg->member_status as $i => $item) {
            if ($i > 1) {
                $row = [];
                $row[] = [
                    'scope' => 'col',
                    'text' => $item
                ];
                $check = isset($index->can_write) && is_array($index->can_write) && in_array($i, $index->can_write) ? ' checked' : '';
                $row[] = [
                    'class' => 'center',
                    'text' => '<label data-text="{LNG_Writing}"><input type=checkbox name=can_write[] title="{LNG_Members of this group can create or edit}" value='.$i.$check.'></label>'
                ];
                $check = isset($index->can_config) && is_array($index->can_config) && in_array($i, $index->can_config) ? ' checked' : '';
                $row[] = [
                    'class' => 'center',
                    'text' => '<label data-text="{LNG_Settings}"><input type=checkbox name=can_config[] title="{LNG_Members of this group can setting the module (not recommend)}" value='.$i.$check.'></label>'
                ];
                $table->addRow($row, [
                    'class' => 'status'.$i
                ]);
            }
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
