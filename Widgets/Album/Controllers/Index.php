<?php
/**
 * @filesource Widgets/Album/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Album\Controllers;

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
        if (!empty($query_string['module']) && preg_match('/([0-9]+)_([0-9]+)/', $query_string['module'], $match)) {
            $query_string['rows'] = $match[1];
            $query_string['cols'] = $match[2];
        }
        $query_string['rows'] = empty($query_string['rows']) ? 2 : max(1, (int) $query_string['rows']);
        if (empty($query_string['cols']) || !in_array($query_string['cols'], array(1, 2, 4, 6, 8))) {
            $query_string['cols'] = 4;
        } else {
            $query_string['cols'] = (int) $query_string['cols'];
        }
        // คืนค่า HTML
        return \Widgets\Album\Views\Index::render($query_string);
    }
}
