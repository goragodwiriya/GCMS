<?php
/**
 * @filesource modules/index/views/activate.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Activate;

use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=view
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงข้อมูลสมาชิก
     *
     * @param Request $request
     *
     * @return object
     */
    public function render(Request $request)
    {
        // ตรวจสอบข้อมูล
        $user = \Index\User\Model::getUserByActivateCode($request->get('id')->topic());
        if ($user) {
            // activate
            \Index\User\Model::activateUser($user);
            // ข้อมูลแสดงผล (สำเร็จ)
            $details = array(
                '/{DETAIL}/' => Language::get('<b>Congratulations!</b> your members have already confirmed. You can use your email address and password sent with the email address used to login.'),
                '/{CLASS}/' => 'message'
            );
        } else {
            // ข้อมูลแสดงผล (ไม่สำเร็จ)
            $details = array(
                '/{DETAIL}/' => Language::get('<b>Sorry!</b> can not find it registered. Information of registration may have expired or your registration may be confirmed.'),
                '/{CLASS}/' => 'error'
            );
        }
        // /member/activate.html
        $template = Template::create('member', 'member', 'activate');
        $template->add($details);
        // คืนค่า
        return (object) array(
            'detail' => $template->render(),
            'keywords' => self::$cfg->web_title,
            'description' => self::$cfg->web_description,
            'topic' => Language::get('Activate').' '.self::$cfg->web_title,
            'menu' => 'member'
        );
    }
}
