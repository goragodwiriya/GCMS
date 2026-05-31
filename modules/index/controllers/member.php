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
     * @param Request $request
     */
    public function profile(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'profile');
    }

    /**
     * @param Request $request
     */
    public function sendmail(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'sendmail');
    }

    /**
     * @param Request $request
     */
    public function register(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'register');
    }

    /**
     * @param Request $request
     */
    public function forgot(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'forgot');
    }

    /**
     * @param Request $request
     */
    public function login(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'login');
    }

    /**
     * @param Request $request
     */
    public function member(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'member');
    }

    /**
     * @param Request $request
     */
    public function activate(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'activate');
    }

    /**
     * @param Request $request
     */
    public function terms(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'terms');
    }

    /**
     * @param Request $request
     */
    public function privacy(Request $request)
    {
        return \Index\Member\View::create()->render($request, 'privacy');
    }
}
