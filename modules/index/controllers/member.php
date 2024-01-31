<?php
/**
 * @filesource modules/index/controllers/member.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Member;

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
     * แสดงผลฟอร์ม ที่เรียกมาจาก GModal
     *
     * @param Request $request
     */
    public function modal(Request $request)
    {
        $action = $request->post('action')->toString();
        if ($action === 'register') {
            $page = \Index\Register\View::create()->render($request, true);
        } elseif ($action === 'forgot') {
            $page = \Index\Forgot\View::create()->render($request, true);
        } elseif ($action === 'login') {
            $page = \Index\Dologin\View::create()->render($request);
        } else {
            // 404
            $page = createClass('Index\Error\Controller')->init('index');
        }
        echo json_encode($page);
    }

    /**
     * @param Request $request
     */
    public function editprofile(Request $request)
    {
        return \Index\Editprofile\View::create()->render($request);
    }

    /**
     * @param Request $request
     */
    public function sendmail(Request $request)
    {
        return \Index\Sendmail\View::create()->render($request);
    }

    /**
     * @param Request $request
     */
    public function register(Request $request)
    {
        return \Index\Register\View::create()->render($request, false);
    }

    /**
     * @param Request $request
     */
    public function forgot(Request $request)
    {
        return \Index\Forgot\View::create()->render($request);
    }

    /**
     * @param Request $request
     */
    public function dologin(Request $request)
    {
        return \Index\Dologin\View::create()->render($request);
    }

    /**
     * @param Request $request
     */
    public function member(Request $request)
    {
        return \Index\View\View::create()->render($request);
    }

    /**
     * @param Request $request
     */
    public function activate(Request $request)
    {
        return \Index\Activate\View::create()->render($request);
    }
}
