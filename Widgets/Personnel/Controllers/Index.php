<?php
/**
 * @filesource Widgets/Personnel/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Personnel\Controllers;

use Gcms\Gcms;
use Personnel\Index\Controller;

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
        if (!empty($query_string['module'])) {
            if (preg_match('/([0-9]+)(_([a-z]+))?/', $query_string['module'], $match)) {
                $query_string['cat'] = (int) $match[1];
                $query_string['menu'] = isset($match[3]);
                $query_string['module'] = 'personnel';
            }
            $query_string['cat'] = isset($query_string['cat']) ? $query_string['cat'] : 0;
            // ตรวจสอบโมดูล
            $index = Gcms::$module->findByModule($query_string['module']);
            if ($index) {
                $id = uniqid();
                $widget = array();
                $widget[] = '<div id="'.$id.'" class=widget_personnel>';
                foreach (\Personnel\Lists\Model::getItems($index->module_id, $query_string['cat']) as $i => $item) {
                    $url = Controller::url($index->module, $item->id);
                    // image
                    if (is_file(ROOT_PATH.DATA_FOLDER.'personnel/'.$item->picture)) {
                        $img = WEB_URL.DATA_FOLDER.'personnel/'.$item->picture;
                    } else {
                        $img = WEB_URL.'modules/personnel/img/noimage.jpg';
                    }
                    $widget[] = '<div class='.($i == 0 ? 'currItem' : 'item').'>';
                    $widget[] = '<a class=thumbnail href="'.$url.'"><img src='.$img.' alt=personnel class=nozoom></a>';
                    $widget[] = '<p class=detail>';
                    $widget[] = '<a class=name href="'.$url.'">'.$item->name.'</a>';
                    $widget[] = '<a class=position href="'.Controller::url($index->module, 0, $item->category_id).'">'.$item->position.'</a>';
                    $widget[] = '</p>';
                    $widget[] = '</div>';
                }
                $widget[] = '</div>';
                if (!empty($query_string['menu'])) {
                    $widget[] = '<nav class="sidemenu margin-top"><ul>';
                    foreach (\Index\Category\Model::categories($index->module_id) as $category_id => $topic) {
                        $widget[] = '<li><a href="'.Gcms::createUrl($index->module, '', 0, 0, 'cat='.$category_id).'"><span>'.$topic.'</span></a></li>';
                    }
                    $widget[] = '</ul></nav>';
                }
                $widget[] = '<script>';
                $widget[] = 'initPersonnelWidget("'.$id.'");';
                $widget[] = '</script>';
                // คืนค่า HTML
                return implode('', $widget);
            }
        }
        return '';
    }
}
