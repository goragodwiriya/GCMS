<?php
/**
 * @filesource modules/gallery/views/admin/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Settings;

use Kotchasan\Html;
use Kotchasan\HtmlTable;
use Kotchasan\Http\Request;

/**
 * module=gallery-settings
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
            'action' => 'index.php/gallery/model/admin/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Thumbnail}'
        ]);
        $groups = $fieldset->add('groups', [
            'label' => '{LNG_Size of} {LNG_Thumbnail}',
            'comment' => '{LNG_The size of the small picture (Thumbnail) is displayed in albums and galleries (pixels), resize automatically}'
        ]);
        // icon_width
        $groups->add('text', [
            'id' => 'icon_width',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'width',
            'label' => '{LNG_Width}',
            'value' => $index->icon_width
        ]);
        // icon_height
        $groups->add('text', [
            'id' => 'icon_height',
            'labelClass' => 'g-input icon-height',
            'itemClass' => 'width',
            'label' => '{LNG_Height}',
            'value' => $index->icon_height
        ]);
        // image_width
        $fieldset->add('text', [
            'id' => 'image_width',
            'labelClass' => 'g-input icon-width',
            'itemClass' => 'item',
            'label' => '{LNG_Size of} {LNG_Image} ({LNG_Width})',
            'comment' => '{LNG_The size of the images are stored as pixels. The image will be resized automatically.}',
            'value' => $index->image_width
        ]);
        // img_typies
        $fieldset->add('checkboxgroups', [
            'id' => 'img_typies',
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Types of files that can be uploaded} ({LNG_Please select at least one item})',
            'labelClass' => 'g-input icon-thumbnail',
            'options' => ['jpg' => 'jpg', 'jpeg' => 'jpeg', 'webp' => 'webp', 'gif' => 'gif', 'png' => 'png'],
            'value' => $index->img_typies
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Display}'
        ]);
        $groups = $fieldset->add('groups', [
            'comment' => '{LNG_The number of items displayed per page}'
        ]);
        // cols
        $groups->add('select', [
            'id' => 'cols',
            'labelClass' => 'g-input icon-cols',
            'itemClass' => 'width',
            'label' => '{LNG_Cols}',
            'options' => [2 => 2, 4 => 4, 6 => 6, 8 => 8],
            'value' => $index->cols
        ]);
        // rows
        $groups->add('select', [
            'id' => 'rows',
            'labelClass' => 'g-input icon-rows',
            'itemClass' => 'width',
            'label' => '{LNG_Rows}',
            'options' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14],
            'value' => $index->rows
        ]);
        // sort
        $sorts = ['ID', '{LNG_date}', '{LNG_Random}'];
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
            ['text' => '{LNG_Viewing}'],
            ['text' => '{LNG_Upload}'],
            ['text' => '{LNG_Settings}']
        ]);
        foreach ([-1 => '{LNG_Guest}'] + self::$cfg->member_status as $i => $item) {
            if ($i != 1) {
                $row = [];
                $row[] = [
                    'scope' => 'col',
                    'text' => $item
                ];
                $check = isset($index->can_view) && is_array($index->can_view) && in_array($i, $index->can_view) ? ' checked' : '';
                $row[] = [
                    'class' => 'center',
                    'text' => '<label data-text="{LNG_Viewing}"><input type=checkbox name=can_view[] title="{LNG_Members of this group can see the content}" value='.$i.$check.'></label>'
                ];
                $check = isset($index->can_write) && is_array($index->can_write) && in_array($i, $index->can_write) ? ' checked' : '';
                $row[] = [
                    'class' => 'center',
                    'text' => $i > 0 ? '<label data-text="{LNG_Upload}"><input type=checkbox name=can_write[] title="{LNG_Members of this group can create or edit}" value='.$i.$check.'></label>' : ''
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
