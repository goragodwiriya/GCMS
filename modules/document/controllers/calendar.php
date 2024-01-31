<?php
/**
 * @filesource modules/document/controllers/calendar.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Calendar;

use Gcms\Gcms;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * หน้าแสดงบทความจากวันที่
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * หน้าแสดงบทความจากวันที่
     *
     * @param Request $request
     * @param object  $module  ข้อมูลโมดูลจาก database
     *
     * @return object
     */
    public function init(Request $request, $module)
    {
        // ลิสต์รายการ tag
        $index = \Document\Stories\Model::calendar($request, $module);
        if ($index) {
            $index->module = 'document';
            $index->rows = self::$cfg->document_rows;
            $index->cols = self::$cfg->document_cols;
            $index->style = self::$cfg->document_style;
            $index->new_date = 0;
            $index->topic = Language::get('Articles written at').' '.Date::format($index->d, 'd M Y');
            $index->description = $index->topic;
            $index->keywords = $index->topic;
            $index->detail = '';
            return createClass('Document\Stories\View')->index($request, $index);
        }
        // 404
        return createClass('Index\Error\Controller')->init('document');
    }

    /**
     * ฟังก์ชั่นอ่านข้อมูลสำหรับการแสดงบนปฏิทิน
     *
     * @param array $settings         ค่ากำหนดของปฎิทิน
     * @param int   $first_date       วันที่ 1 (mktime)
     * @param int   $first_next_month วันที่ 1 ของเดือนถัดไป (mktime)
     *
     * @return array
     */
    public function calendar($settings, $first_date, $first_next_month)
    {
        return createClass('Document\Calendar\Model')->calendar($settings, $first_date, $first_next_month);
    }

    /**
     * ฟังก์ชั่นเรียมาจากปฏิทินเพื่อแสดงทูลทิป
     *
     * @param Request $request
     * @param array   $query_string ค่าที่ส่งมาจาก tooltip
     * @param array   $settings     ค่ากำหนด
     */
    public function tooltip(Request $request, $settings)
    {
        if (preg_match('/^calendar\-([0-9]+){0,2}\-([0-9]+){0,2}\-([0-9]+){0,4}\-([0-9_]+)$/', $request->request('id')->toString(), $match)) {
            $ids = array();
            foreach (explode('_', $match[4]) as $id) {
                $ids[] = (int) $id;
            }
            if (!empty($ids)) {
                echo '<div id=calendar-tooltip>';
                echo '<h5>'.Date::format("$match[3]-$match[2]-$match[1]", 'd M Y').'</h5>';
                foreach (createClass('Document\Calendar\Model')->tooltip($ids, $settings) as $item) {
                    echo '<a href="'.WEB_URL.'index.php?module='.$item['module'].'&amp;id='.$item['id'].'" title="'.$item['description'].'">'.$item['topic'].'</a>';
                }
                echo '</div>';
            }
        }
    }

    /**
     * สร้าง URL สำหรับการแสดงรายการภสยในวันที่เลือก
     *
     * @param string $d วันที่
     *
     * @return string
     */
    public function url($d)
    {
        return Gcms::createUrl('calendar', $d);
    }
}
