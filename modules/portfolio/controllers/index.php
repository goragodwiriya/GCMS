<?php
/**
 * @filesource modules/portfolio/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Portfolio\Index;

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
            if ($index) {
                if ($request->request('id')->exists()) {
                    // ดูรายการ
                    $page = createClass('Portfolio\View\View')->index($request, $index);
                } else {
                    // หน้าแสดงรายการ
                    $page = createClass('Portfolio\Lists\View')->index($request, $index);
                }
                if ($page) {
                    return $page;
                }
            }
        }
        // 404
        return createClass('Index\Error\Controller')->init('portfolio');
    }

    /**
     * ฟังก์ชั่นสร้าง URL
     *
     * @param string $module ชื่อโมดูล
     * @param int    $id     ID
     *
     * @return string
     */
    public static function url($module, $id)
    {
        return \Gcms\Gcms::createUrl($module, '', 0, 0, 'id='.$id);
    }
}
