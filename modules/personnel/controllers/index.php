<?php
/**
 * @filesource modules/personnel/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Index;

use Gcms\Gcms;
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
        if (MAIN_INIT === 'indexhtml') {
            // ตรวจสอบโมดูลและอ่านข้อมูลโมดูล
            $index = \Index\Module\Model::getDetails($index);
            if ($request->request('id')->exists()) {
                // แสดงข้อมูลบุคคลาการ
                $page = createClass('Personnel\View\View')->index($request, $index);
            } else {
                // แสดงรายการบุคลากร
                $page = createClass('Personnel\Lists\View')->index($request, $index);
            }
        }
        if (!$page) {
            // 404
            $page = createClass('Index\Error\Controller')->init('personnel');
        }
        return $page;
    }

    /**
     * ฟังก์ชั่นสร้าง URL
     *
     * @param string $module      ชื่อโมดูล
     * @param int    $id          ID
     * @param int    $category_id หมวด
     *
     * @return string
     */
    public static function url($module, $id, $category_id = 0)
    {
        if (empty($id)) {
            return Gcms::createUrl($module, '', $category_id);
        } else {
            return Gcms::createUrl($module, '', $category_id, 0, 'id='.$id);
        }
    }
}
