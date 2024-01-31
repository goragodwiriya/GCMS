<?php
/**
 * @filesource modules/index/views/dologin.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Dologin;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=dologin
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * หน้า login
     *
     * @param Request $request
     *
     * @return object
     */
    public function render(Request $request)
    {
        $sign_in = Language::get('Sign in');
        $index = (object) array(
            'canonical' => WEB_URL.'index.php?module=dologin',
            'topic' => $sign_in,
            'description' => self::$cfg->web_description,
            'menu' => 'dologin'
        );
        $template = Template::create('member', 'member', 'loginfrm');
        $template->add(array(
            '/{TOKEN}/' => $request->createToken(),
            '/{EMAIL}/' => isset(Login::$login_params['username']) ? Login::$login_params['username'] : '',
            '/{PASSWORD}/' => isset(Login::$login_params['password']) ? Login::$login_params['password'] : '',
            '/{FACEBOOK}/' => empty(self::$cfg->facebook_appId) ? 'hidden' : 'facebook',
            '/{TOPIC}/' => $index->topic,
            '/{SUBTITLE}/' => $index->description,
            '/{PLACEHOLDER}/' => Gcms::getLoginPlaceholder(),
            '/{WEBTITLE}/' => self::$cfg->web_title,
            '/{WEBURL}/' => WEB_URL,
            '/{ICON}/' => Gcms::usernameIcon(),
            '/{PLACEHOLDER}/' => Gcms::getLoginPlaceholder(),
            '/{REMEMBERME}/' => $request->cookie('login_remember')->toBoolean() ? ' checked' : ''
        ));
        $index->detail = Language::trans($template->render());
        $index->keywords = $index->topic;
        if (isset(Gcms::$view)) {
            Gcms::$view->addBreadcrumb($index->canonical, $sign_in);
        }
        return $index;
    }
}
