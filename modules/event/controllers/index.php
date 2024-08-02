<?php
/**
 * @filesource event/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Index;

use Kotchasan\Http\Request;

/**
 * Controller หลัก สำหรับแสดง frontend ของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Controller หลักของโมดูล ใช้เพื่อตรวจสอบว่าจะเรียกหน้าไหนมาแสดงผล
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        if ($request->request('id')->exists()) {
            // แสดงรายการที่เลือก
            $className = 'Event\View\View';
        } elseif ($request->request('d')->exists()) {
            // แสดง event รายวัน
            $className = 'Event\Day\View';
        } else {
            // แสดง event รายเดือน
            $className = 'Event\Month\View';
        }
        // ตรวจสอบโมดูลและอ่านข้อมูลโมดูล
        $index = \Index\Module\Model::getDetails($index);
        if ($index && MAIN_INIT === 'indexhtml') {
            $page = createClass($className)->index($request, $index);
        }
        if (!empty($page)) {
            return $page;
        }
        // 404
        return createClass('Index\Error\Controller')->init('event');
    }
}
