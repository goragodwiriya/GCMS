<?php
/**
 * @filesource modules/video/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Index;

use Kotchasan\Http\Request;

/**
 * Public video front controller — renders the video listing page,
 * same shape as Portfolio\Index\Controller::init().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function init(Request $request, $index)
    {
        if (MAIN_INIT === 'indexhtml') {
            $data = \Video\Index\Model::get($request, $index);
            return \Video\Index\View::create()->render($request, $data);
        }

        return \Index\Error\Controller::create()->init('video');
    }

    /**
     * URL of the video listing page.
     *
     * @param string $module
     *
     * @return string
     */
    public static function url($module)
    {
        return \Web\Gcms::createUrl($module);
    }
}
