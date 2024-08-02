<?php
/**
 * @filesource Widgets/Textlink/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlink\Controllers;

use Kotchasan\Date;

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
        if (defined('MAIN_INIT') && preg_match('/[a-z0-9]{1,11}/', $query_string['module'])) {
            // อ่านข้อมูล
            $textlinks = \Widgets\Textlink\Models\Index::get((int) date('n'), (int) date('j'), (int) date('Y'));
            if (!empty($textlinks)) {
                if (empty($query_string['module'])) {
                    // ไม่ได้กำหนดลิงค์มา ใช้รายการแรกที่พบ
                    $textlink = reset($textlinks);
                } elseif (isset($textlinks[$query_string['module']])) {
                    // กำหนดชื่อลิงค์มา
                    $textlink = $textlinks[$query_string['module']];
                } else {
                    // ไม่มีชื่อลิงค์ที่ต้องการ
                    return '';
                }
                $t = reset($textlink);
                switch ($t['type']) {
                    case 'banner':
                        return \Widgets\Textlink\Views\Index::banner($textlink);
                    case 'custom':
                        return \Widgets\Textlink\Views\Index::custom($textlink);
                    case 'slideshow':
                        return \Widgets\Textlink\Views\Index::slideshow($textlink, $t['type']);
                    default:
                        return \Widgets\Textlink\Views\Index::template($textlink);
                }
            }
        }
    }
}
