<?php
/**
 * @filesource Widgets/Calendar/Models/Get.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Calendar\Models;

use Kotchasan\Http\Request;

/**
 * แสดงปฎิทิน (Ajax called)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Get extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลปฏิทินจากเดือนและปีที่ส่งมา
     *
     * @param Request $request
     *
     * @return JSON
     */
    public function toJSON(Request $request)
    {
        $className = ucfirst($request->post('owner')->filter('a-z')).'\Calendar\Model';
        if (class_exists($className) && method_exists($className, 'widget')) {
            $result = createClass($className)->widget($request);
            echo json_encode($result);
        }
    }
}
