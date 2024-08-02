<?php
/**
 * @filesource modules/document/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Index;

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
        if ($request->request('alias')->exists() || $request->request('id')->exists()) {
            // หน้าแสดงบทความ
            if (MAIN_INIT === 'amphtml') {
                $page = createClass('Document\Amp\View')->index($request, $index);
            } else {
                $page = createClass('Document\View\View')->index($request, $index);
            }
        } elseif (MAIN_INIT === 'indexhtml') {
            // ตรวจสอบโมดูลและอ่านข้อมูลโมดูล
            $module = \Document\Module\Model::get($request, $index);
            if ($module) {
                if (!empty($module->category_id) || empty($module->categories) || empty($module->category_display)) {
                    // เลือกหมวดมา หรือไม่มีหมวด หรือปิดการแสดงผลหมวดหมู่ แสดงรายการบทความ
                    $stories = \Document\Stories\Model::stories($request, $module);
                    if (!empty($stories)) {
                        $page = createClass('Document\Stories\View')->index($request, $stories);
                    }
                } else {
                    // หน้าแสดงรายการหมวดหมู่
                    $page = createClass('Document\Categories\View')->index($request, $module);
                }
            }
        }
        if (empty($page)) {
            // ไม่พบหน้าที่เรียก
            $page = createClass('Index\Error\Controller')->init('document');
        }
        return $page;
    }

    /**
     * ฟังก์ชั่นสร้าง URL
     *
     * @param string $module ชื่อโมดูล
     * @param string $alias  alias ของบทความ
     * @param int    $id     ID
     * @param bool   $encode (option) true=เข้ารหัสด้วย rawurlencode ด้วย (default true)
     *
     * @return string
     */
    public static function url($module, $alias, $id, $encode = true)
    {
        if (self::$cfg->module_url == 1) {
            return Gcms::createUrl($module, $alias, 0, 0, '', $encode);
        } else {
            return Gcms::createUrl($module, '', 0, $id, '', $encode);
        }
    }
}
