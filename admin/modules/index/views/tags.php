<?php
/**
 * @filesource modules/index/views/tags.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Tags;

use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=tags
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ตารางรายการ Tags
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $section = Html::create('div');
        $section->add('div', array(
            'class' => 'subtitle',
            'innerHTML' => '{LNG_Tag listings can be listed in the Widgets}'
        ));
        $list = $section->add('ol', array(
            'class' => 'editinplace_list',
            'id' => 'config_status'
        ));
        foreach (\Index\Tags\Model::all() as $item) {
            $row = $list->add('li', array(
                'id' => 'config_status_'.$item['id']
            ));
            $row->add('span', array(
                'innerHTML' => '{LNG_Clicked} [ '.$item['count'].' ]',
                'class' => 'no'
            ));
            $row->add('span', array(
                'id' => 'config_status_delete_'.$item['id'],
                'class' => 'icon-delete',
                'title' => '{LNG_Delete}'
            ));
            $row->add('span', array(
                'id' => 'config_status_name_'.$item['id'],
                'innerHTML' => $item['tag'],
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
            'innerHTML' => '{LNG_Add New} {LNG_Tags}'
        ));
        $section->script('initEditInplace("config_status", "index/model/tags/action");');
        return $section->render();
    }
}
