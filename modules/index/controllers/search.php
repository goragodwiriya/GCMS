<?php
/**
 * @filesource modules/index/controllers/search.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Search;

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
     * @param object  $module  ข้อมูลโมดูลจาก database
     *
     * @return object||null คืนค่าข้อมูลหน้าที่เรียก ไม่พบคืนค่า null
     */
    public function init(Request $request, $module)
    {
        return \Index\Search\View::create()->render(\Index\Search\Model::findAll($request, $module));
    }
}
