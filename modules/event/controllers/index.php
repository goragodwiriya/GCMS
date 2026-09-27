<?php
/**
 * @filesource modules/event/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Index;

use Kotchasan\Http\Request;

/**
 * Public event front controller — branches between the month calendar,
 * the day listing (?d=YYYY-MM-DD) and the single event view (?id=),
 * same shape as the old gcms241021 Event\Index\Controller::init().
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
            if ($request->get('id')->exists()) {
                // แสดง event ที่เลือก
                $data = \Event\View\Model::get($request, $index);
                if ($data !== null) {
                    return \Event\View\View::create()->render($data);
                }
            } elseif ($request->get('d')->exists()) {
                // แสดง event รายวัน
                $data = \Event\Day\Model::get($request, $index);
                if ($data !== null) {
                    return \Event\Day\View::create()->render($data);
                }
            } else {
                // แสดงปฏิทินรายเดือน
                return \Event\Month\View::create()->render($request, $index);
            }
        }

        return \Index\Error\Controller::create()->init('event');
    }

    /**
     * URL of the event listing / a single event.
     *
     * @param string $module
     * @param int    $id
     * @param string $date  YYYY-MM-DD (day view)
     *
     * @return string
     */
    public static function url($module, $id = 0, $date = '')
    {
        $params = [];
        if ($id > 0) {
            $params['id'] = $id;
        }
        if ($date !== '') {
            $params['d'] = $date;
        }
        return \Web\Gcms::createUrl($module, '', 0, 0, $params);
    }
}
