<?php
/**
 * @filesource modules/index/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\View;

use Gcms\Gcms;
use Gcms\Login;
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
        $topic = Language::get('Personal information').' '.self::$cfg->web_title;
        if (Login::isMember()) {
            $user = \Index\User\Model::getUserById($request->request('id')->toInt());
            if ($user) {
                $social = array(1 => 'icon-facebook', 2 => 'icon-google');
                // /member/view.html
                $template = Template::create('member', 'member', 'view');
                $template->add(array(
                    '/{ID}/' => $user->id,
                    '/{EMAIL}/' => $user->email,
                    '/{NAME}/' => $user->name,
                    '/{SEX}/' => $user->sex === 'f' || $user->sex === 'm' ? $user->sex : 'u',
                    '/{DATE}/' => $user->create_date,
                    '/{WEBSITE}/' => $user->website,
                    '/{VISITED}/' => $user->visited,
                    '/{LASTVISITED}/' => $user->lastvisited,
                    '/{POST}/' => number_format($user->post),
                    '/{REPLY}/' => number_format($user->reply),
                    '/{STATUS}/' => isset(self::$cfg->member_status[$user->status]) ? self::$cfg->member_status[$user->status] : 'Unknow',
                    '/{COLOR}/' => $user->status,
                    '/{SOCIAL}/' => isset($social[$user->social]) ? $social[$user->social] : '',
                    '/{TOPIC}/' => $topic
                ));
                // breadcrumbs
                $canonical = WEB_URL.'index.php?module=member&amp;id='.$user->id;
                Gcms::$view->addBreadcrumb($canonical, $topic);
                // คืนค่า
                return (object) array(
                    'detail' => $template->render(),
                    'keywords' => self::$cfg->web_title,
                    'description' => self::$cfg->web_description,
                    'topic' => $topic,
                    'canonical' => $canonical,
                    'menu' => 'member'
                );
            }
            // ไม่พบสมาชิก

            return createClass('Index\Error\Controller')->init('index');
        } else {
            // ไม่ได้ login
            return createClass('Index\Error\Controller')->init('index', 'Members Only');
        }
    }
}
