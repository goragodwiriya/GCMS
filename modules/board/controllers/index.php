<?php
/**
 * @filesource modules/board/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Index;

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
        // ตรวจสอบโมดูลและอ่านข้อมูลโมดูล
        $module = \Board\Module\Model::get($request, $index);
        if ($module && MAIN_INIT === 'indexhtml') {
            if ($request->request('wbid')->exists() || $request->request('id')->exists()) {
                // หน้าแสดงกระทู้
                $page = createClass('Board\View\View')->index($request, $module);
            } elseif (!empty($module->category_id) || empty($module->categories) || empty($module->category_display)) {
                // เลือกหมวดมา หรือไม่มีหมวด หรือปิดการแสดงผลหมวดหมู่ แสดงรายการกระทู้
                $page = createClass('Board\Stories\View')->index($request, $module);
            } else {
                // หน้าแสดงรายการหมวดหมู่
                $page = createClass('Board\Categories\View')->index($request, $module);
            }
        }
        if (empty($page)) {
            // ไม่พบหน้าที่เรียก (board)
            $page = createClass('Index\Error\Controller')->init('board');
        }
        return $page;
    }

    /**
     * ฟังก์ชั่นสร้าง URL
     *
     * @param string $module
     * @param int    $id
     *
     * @return string
     */
    public static function url($module, $id)
    {
        return Gcms::createUrl($module, '', 0, 0, 'wbid='.$id);
    }
}
