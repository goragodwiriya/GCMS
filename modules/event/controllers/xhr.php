<?php
/**
 * @filesource event/controllers/xhr.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Xhr;

use Kotchasan\Http\Request;

/**
 * แสดงปฎิทิน (Ajax called)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * แสดงปฎิทิน
     */
    public function get(Request $request)
    {
        if ($request->isReferer()) {
            echo \Event\Calendar\Controller::render($request);
        }
    }
}
