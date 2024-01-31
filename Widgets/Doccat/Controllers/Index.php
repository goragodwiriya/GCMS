<?php
/**
 * @filesource Widgets/Doccat/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Doccat\Controllers;

use Document\Index\Controller;
use Gcms\Gcms;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * แสดงผล Widget
     *
     * @param array $query_string ข้อมูลที่ส่งมาจากการเรียก Widget
     *
     * @return string
     */
    public function get($query_string)
    {
        if ($index = Gcms::$module->findByModule($query_string['module'])) {
            $list = \Widgets\Doccat\Models\Index::categories((int) $index->module_id);
        } else {
            $list = array();
        }
        foreach (\Widgets\Doccat\Models\Index::topics((int) $index->module_id) as $item) {
            $list[] = $item;
        }
        // จัดกลุ่มข้อมูลตาม parent_id
        $trees = array();
        foreach ($list as $items) {
            $trees[$items['parent_id']][] = $items;
        }
        if (!empty($trees)) {
            $id = uniqid();
            $widget = '<nav class=tree_menu id="'.$id.'">'.$this->showTree($index->module, $trees, 0).'</nav>';
            $widget .= '<script>initDocCat("'.$id.'")</script>';
        } else {
            $widget = '';
        }
        // คืนค่า HTML
        return $widget;
    }

    /**
     * @param string $module
     * @param array  $array
     * @param int    $parent_id
     *
     * @return mixed
     */
    public function showTree($module, $array, $parent_id)
    {
        $row = '<ul>';
        foreach ($array[$parent_id] as $item) {
            $d = "input_$item[id]_$parent_id";
            $has_child = isset($array[$item['id']]);
            if ($parent_id == 0) {
                $class = $has_child ? 'icon-expand' : 'icon-uncheck';
                $id = $parent_id.'_'.$item['id'];
            } else {
                $class = 'icon-dev';
                $id = $item['id'].'_'.$parent_id;
            }
            if ($parent_id == 0) {
                $url = Gcms::createUrl($module, '', $item['id']);
            } else {
                $url = Controller::url($module, $item['alias'], $item['id']);
            }
            $row .= '<li><span class='.$class.' id=doccat_'.$id.'></span><a href="'.$url.'">'.$item['topic'].'</a>';
            if ($has_child) {
                $row .= $this->showTree($module, $array, $item['id']);
            }
            $row .= '</li>';
        }
        $row .= '</ul>';
        // คืนค่า HTML
        return $row;
    }
}
