<?php
/**
 * @filesource Widgets/Video/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Video\Controllers;

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
            if (preg_match('/^([0-9]+)_([0-9]+)$/', $query_string['module'], $match)) {
                $cols = max(1, (int) $match[1]);
                $count = max(1, (int) $match[2]);
                $videos = \Widgets\Video\Models\Index::get(0, $count * $cols);
            } elseif (preg_match('/[a-zA-Z0-9\-_]{11,11}/', $query_string['module'])) {
                $cols = 1;
                $count = 1;
                $videos = array(
                    array(
                        'id' => 0,
                        'topic' => '',
                        'youtube' => $query_string['module']
                    )
                );
            } elseif (preg_match('/[0-9]+/', $query_string['module'])) {
                $cols = 1;
                $count = 1;
                $videos = \Widgets\Video\Models\Index::get((int) $query_string['module'], 1);
            } else {
                $cols = 2;
                $count = 2;
                $videos = \Widgets\Video\Models\Index::get(0, $count);
            }
            if ($cols == 1 && $count == 1 && MAIN_INIT == 'amphtml') {
                return \Widgets\Video\Views\Amp::render($videos);
            }
            // คืนค่า HTML
            return \Widgets\Video\Views\Index::render($cols, $count, $videos, $query_string);
        }
        return '';
    }
}
