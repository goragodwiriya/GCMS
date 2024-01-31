<?php
/**
 * @filesource modules/index/views/welcome.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Welcome;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * Login, Forgot
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\View
{
    /**
     * ฟอร์มเข้าระบบ
     *
     * @param Request $request
     *
     * @return object
     */
    public static function login(Request $request)
    {
        // template
        $template = Template::create('', '', 'login');
        $template->add(array(
            '/<FACEBOOK>(.*)<\/FACEBOOK>/s' => empty(self::$cfg->facebook_appId) || !self::$cfg->demo_mode ? '' : '\\1',
            '/{TOKEN}/' => $request->createToken(),
            '/{PLACEHOLDER}/' => Gcms::getLoginPlaceholder(),
            '/{ICON}/' => Gcms::usernameIcon(),
            '/{EMAIL}/' => isset(Login::$login_params['username']) ? Login::$login_params['username'] : '',
            '/{PASSWORD}/' => isset(Login::$login_params['password']) ? Login::$login_params['password'] : '',
            '/{MESSAGE}/' => Language::get(Login::$login_message),
            '/{CLASS}/' => empty(Login::$login_message) ? 'hidden' : (empty(Login::$login_input) ? 'message' : 'error'),
            '/{LOGIN_ACTION}/' => $request->getUri()->withoutParams('action'),
            '/{REMEMBER}/' => $request->cookie('login_remember')->toBoolean() ? 'checked' : ''
        ));
        return (object) array(
            'content' => $template->render(),
            'title' => Language::get('Login with an existing account')
        );
    }

    /**
     * ฟอร์มขอรหัสผ่านใหม่
     *
     * @param Request $request
     *
     * @return object
     */
    public static function forgot(Request $request)
    {
        // template
        $template = Template::create('', '', 'forgot');
        $template->add(array(
            '/{TOKEN}/' => $request->createToken(),
            '/{PLACEHOLDER}/' => Gcms::getLoginPlaceholder(),
            '/{ICON}/' => Gcms::usernameIcon(),
            '/{EMAIL}/' => isset(Login::$login_params['username']) ? Login::$login_params['username'] : '',
            '/{MESSAGE}/' => Login::$login_message,
            '/{CLASS}/' => empty(Login::$login_message) ? 'hidden' : (empty(Login::$login_input) ? 'message' : 'error')
        ));
        return (object) array(
            'content' => $template->render(),
            'title' => Language::get('Get new password')
        );
    }
}
