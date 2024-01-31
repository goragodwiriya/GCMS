<?php
/**
 * @filesource modules/index/views/forgot.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Forgot;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=forgot
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * หน้าขอรหัสผ่านใหม่
     *
     * @param Request $request
     * @param bool    $modal   true แสดงแบบ modal, false (default) แสดงหน้าเว็บปกติ
     *
     * @return object
     */
    public function render(Request $request, $modal = false)
    {
        $index = (object) array(
            'canonical' => WEB_URL.'index.php?module=forgot',
            'topic' => Language::get('Request new password'),
            'description' => self::$cfg->web_description
        );
        $template = Template::create('member', 'member', 'forgotfrm');
        $template->add(array(
            '/{WEBTITLE}/' => self::$cfg->web_title,
            '/{TOPIC}/' => $index->topic,
            '/{EMAIL}/' => isset(Login::$login_params['username']) ? Login::$login_params['username'] : '',
            '/{WEBURL}/' => WEB_URL,
            '/{TOKEN}/' => $request->createToken(),
            '/{MODAL}/' => $modal ? 'true' : WEB_URL.'index.php',
            '/{ICON}/' => Gcms::usernameIcon(),
            '/{PLACEHOLDER}/' => Gcms::getLoginPlaceholder()
        ));
        $index->detail = Language::trans($template->render());
        $index->keywords = $index->topic;
        if (isset(Gcms::$view)) {
            Gcms::$view->addBreadcrumb($index->canonical, Language::get('Forgot'));
        }
        // เมนู
        $index->menu = 'forgot';
        return $index;
    }
}
