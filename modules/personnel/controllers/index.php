<?php
/**
 * @filesource modules/personnel/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Index;

use Kotchasan\Http\Request;

/**
 * Controller หลัก สำหรับแสดง frontend ของ Personnel
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * Controller หลักของโมดูล
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        if (MAIN_INIT === 'indexhtml') {
            // หน้ารวมบุคลากร (กรองตามแผนก)
            $department = $request->get('department')->toInt();
            $index->department = $department;
            $index = \Personnel\Index\Model::getPersonnel($index);
            return \Personnel\Index\View::create()->render($index);
        }
        return \Index\Error\Controller::create()->init('personnel');
    }
}
