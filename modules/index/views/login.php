<?php
/**
 * @filesource modules/index/views/login.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Login;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * กรอบสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงกรอบ login
     */
    public function __construct()
    {
        // template ที่กำลังใช้งานอยู่
        if (!empty($_SESSION['skin']) && is_file(APP_PATH.'skin/'.$_SESSION['skin'].'/style.css')) {
            self::$cfg->skin = $_SESSION['skin'];
        }
        Template::init('skin/'.self::$cfg->skin);
    }

    /**
     * ฟอร์มสมาชิก
     *
     * @param array $login
     *
     * @return string
     */
    public function member($login)
    {
        $template = Template::create('member', 'member', 'member');
        if ($template->isEmpty()) {
            $template = Template::create('member', 'member', 'memberfrm');
        }
        $template->add(array(
            '/{WEBTITLE}/' => self::$cfg->web_title,
            '/{SUBTITLE}/' => empty(Login::$login_message) ? self::$cfg->web_description : '<span class=error>'.Login::$login_message.'</span>',
            '/{DISPLAYNAME}/' => empty($login['displayname']) ? (empty($login['email']) ? 'Unname' : $login['email']) : $login['displayname'],
            '/{ID}/' => isset($login['id']) ? (int) $login['id'] : 0,
            '/{STATUS}/' => isset($login['id']) ? $login['status'] : 0,
            '/{ADMIN}/' => Login::adminAccess() ? '' : 'hidden',
            '/{TOKEN}/' => self::$request->createToken(),
            '/{WEBURL}/' => WEB_URL,
            '/{NAME}/' => self::$cfg->member_status[1]
        ));
        return Language::trans($template->render());
    }

    /**
     * ฟอร์มเข้าระบบ
     *
     * @return string
     */
    public function login()
    {
        $template = Template::create('member', 'member', 'login');
        if ($template->isEmpty()) {
            $template = Template::create('member', 'member', 'loginfrm');
        }
        $template->add(array(
            '/{WEBTITLE}/' => self::$cfg->web_title,
            '/{SUBTITLE}/' => empty(Login::$login_message) ? self::$cfg->web_description : '<span class=error>'.Login::$login_message.'</span>',
            '/{EMAIL}/' => isset(Login::$login_params['username']) ? Login::$login_params['username'] : '',
            '/{PASSWORD}/' => isset(Login::$login_params['password']) ? Login::$login_params['password'] : '',
            '/{TOKEN}/' => self::$request->createToken(),
            '/{PLACEHOLDER}/' => \Gcms\Gcms::getLoginPlaceholder(),
            '/{FACEBOOK}/' => empty(self::$cfg->facebook_appId) ? 'hidden' : 'facebook',
            '/{WEBURL}/' => WEB_URL,
            '/{ICON}/' => Gcms::usernameIcon(),
            '/{PLACEHOLDER}/' => Gcms::getLoginPlaceholder(),
            '/{REMEMBERME}/' => self::$request->cookie('login_remember')->toBoolean() ? ' checked' : ''
        ));
        return Language::trans($template->render());
    }
}
