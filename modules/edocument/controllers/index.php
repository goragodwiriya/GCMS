<?php
/**
 * @filesource modules/edocument/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Index;

use Kotchasan\Http\Request;

/**
 * module=edocument
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
            // รายการไฟล์ดาวน์โหลด
            return createClass('Edocument\Index\View')->index($request, $index);
        }
        // 404
        return createClass('Index\Error\Controller')->init('edocument');
    }
}
