<?php
/**
 * @filesource Widgets/Categories/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Categories\Controllers;

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
        if (!empty($query_string['module']) && $index = Gcms::$module->findByModule($query_string['module'])) {
            $menu = '';
            // query หมวด
            foreach (\Index\Category\Model::categories($index->module_id) as $category_id => $topic) {
                $menu .= '<li><a href="'.Gcms::createUrl($index->module, '', $category_id).'"><span>'.$topic.'</span></a></li>';
            }
            if (empty($query_string['itemonly'])) {
                return $menu == '' ? '' : '<ul>'.$menu.'</ul>';
            } else {
                return $menu;
            }
        }
    }
}
