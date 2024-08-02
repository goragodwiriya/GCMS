<?php
/**
 * @filesource modules/index/controllers/main.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Main;

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
     * แสดงผลโมดูล Index
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูลจาก database
     *
     * @return object||null คืนค่าข้อมูลหน้าที่เรียก ไม่พบคืนค่า null
     */
    public function init(Request $request, $index)
    {
        // อ่านข้อมูลโมดูล Index
        $index = \Index\Index\Model::get($index);
        if ($index && MAIN_INIT === 'indexhtml') {
            // view (index)
            return \Index\Index\View::create()->render($index);
        }
        return null;
    }
}
