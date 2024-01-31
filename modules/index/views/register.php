<?php
/**
 * @filesource modules/index/views/register.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Register;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=register
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * หน้าสมัครสมาชิก
     *
     * @param Request $request
     * @param bool    $modal   true แสดงแบบ modal, false (default) แสดงหน้าเว็บปกติ
     *
     * @return object
     */
    public function render(Request $request, $modal = false)
    {
        $index = (object) array(
            'canonical' => WEB_URL.'index.php?module=register',
            'topic' => Language::get('Create new account'),
            'description' => self::$cfg->web_description
        );
        // /member/registerfrm.html
        $template = Template::create('member', 'member', 'registerfrm');
        $template->add(array(
            '/<EMAIL>(.*)<\/EMAIL>/isu' => in_array('email', self::$cfg->login_fields) ? '\\1' : '',
            '/<PHONE>(.*)<\/PHONE>/isu' => in_array('phone1', self::$cfg->login_fields) || !empty(self::$cfg->member_phone) ? '\\1' : '',
            '/<IDCARD>(.*)<\/IDCARD>/isu' => empty(self::$cfg->member_idcard) ? '' : '\\1',
            '/{TOPIC}/' => $index->topic,
            '/{TOKEN}/' => $request->createToken(),
            '/{WEBTITLE}/' => self::$cfg->web_title,
            '/{WEBURL}/' => WEB_URL,
            '/{NEXT}/' => $modal ? 'close' : WEB_URL.'index.php'
        ));
        $index->detail = Language::trans($template->render());
        $index->keywords = $index->topic;
        if (isset(Gcms::$view)) {
            Gcms::$view->addBreadcrumb($index->canonical, Language::get('Register'));
        }
        // เมนู
        $index->menu = 'register';
        return $index;
    }
}
