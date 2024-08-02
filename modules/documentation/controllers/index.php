<?php
/**
 * @filesource modules/documentation/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Index;

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
        $index = \Index\Module\Model::getDetails($index);
        if ($index) {
            if ($request->request('alias')->exists() || $request->request('id')->exists()) {
                if (MAIN_INIT === 'amphtml') {
                    $page = createClass('Documentation\Amp\View')->index($request, $index);
                } else {
                    $page = createClass('Documentation\View\View')->index($request, $index);
                }
            } elseif (MAIN_INIT === 'indexhtml') {
                $page = createClass('Documentation\Index\View')->index($request, $index);
            }
        }
        if (empty($page)) {
            // ไม่พบหน้าที่เรียก (documentation)
            $page = createClass('Index\Error\Controller')->init('documentation');
        }
        return $page;
    }

    /**
     * ฟังก์ชั่นสร้าง URL
     *
     * @param string $module ชื่อโมดูล
     * @param string $alias  alias ของบทความ
     * @param int    $id     ID
     *
     * @return string
     */
    public static function url($module, $alias, $id)
    {
        if (self::$cfg->module_url == 1) {
            return Gcms::createUrl($module, $alias);
        } else {
            return Gcms::createUrl($module, '', 0, $id);
        }
    }
}
