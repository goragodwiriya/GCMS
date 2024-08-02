<?php
/**
 * @filesource modules/board/controllers/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Export;

use Kotchasan\Http\Request;

/**
 * export.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * ส่งออกกระทู้
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     *
     * @return array
     */
    public static function init(Request $request, $index)
    {
        // พิมพ์กระทู้
        return createClass('Board\Export\View')->printer($request, $index);
    }
}
