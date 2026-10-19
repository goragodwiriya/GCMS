<?php
/**
 * @filesource modules/home/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Home\Index;

use Kotchasan\Http\Request;

/**
 * Controller หลัก สำหรับแสดง frontend ของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
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
        if ($index && MAIN_INIT === 'indexhtml') {
            // view (index)
            return \Home\Index\View::create()->render($index);
        }
        return null;
    }
}
