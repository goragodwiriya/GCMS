<?php
/**
 * @filesource modules/index/views/memberstatus.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Memberstatus;

use Kotchasan\Html;

/**
 * module=memberstatus
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * รายการสถานะสมาชิก
     *
     * @return string
     */
    public function render()
    {
        $section = Html::create('div');
        $section->add('div', array(
            'class' => 'subtitle',
            'innerHTML' => '{LNG_Status of membership, the first item (0) means end users and 1 represents the administrator. (The first two items are the items necessary), you can modify the ability of each member of the modules again.}'
        ));
        $list = $section->add('ol', array(
            'class' => 'editinplace_list',
            'id' => 'config_status'
        ));
        foreach (self::$cfg->member_status as $s => $item) {
            $row = $list->add('li', array(
                'id' => 'config_status_'.$s
            ));
            if ($s > 1) {
                $row->add('span', array(
                    'id' => 'config_status_delete_'.$s,
                    'class' => 'icon-delete',
                    'title' => '{LNG_Delete}'
                ));
            } else {
                $row->add('span');
            }
            $row->add('span', array(
                'id' => 'config_status_color_'.$s,
                'title' => self::$cfg->color_status[$s]
            ));
            $row->add('span', array(
                'id' => 'config_status_name_'.$s,
                'innerHTML' => $item,
                'title' => '{LNG_click to edit}'
            ));
        }
        $div = $section->add('div', array(
            'class' => 'submit'
        ));
        $a = $div->add('a', array(
            'class' => 'button add large',
            'id' => 'config_status_add'
        ));
        $a->add('span', array(
            'class' => 'icon-plus',
            'innerHTML' => '{LNG_Add New} {LNG_Member status}'
        ));
        $section->script('initEditInplace("config_status", "index/model/memberstatus/action");');
        $section->script('$E("config_status_color_0").focus();');
        return $section->render();
    }
}
