<?php
/**
 * @filesource modules/index/views/sendmail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Sendmail;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=sendmail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * หน้าส่งอีเมล
     * สมาชิกส่งจดหมายถึงสมาชิก และ แอดมิน
     *
     * @param Request $request
     *
     * @return object
     */
    public function render(Request $request)
    {
        // สมาชิก
        if ($login = Login::isMember()) {
            // ค่าที่ส่งมา
            $to = strtolower($request->request('to')->filter('0-9a-zA-Z'));
            if (preg_match('/^[0-9]+$/', $to)) {
                $reciever = \Index\Sendmail\Model::getUser($to);
                $to = '';
                foreach ($reciever as $id => $item) {
                    $to_msg = empty($item['name']) ? $item['email'] : $item['name'];
                    $to = $id;
                }
            } elseif ($to == 'admin') {
                $to_msg = self::$cfg->member_status[1];
            } else {
                $to = '';
            }
            if ($to != '') {
                // ข้อมูลส่งกลับ
                $index = (object) array(
                    'topic' => Language::get('Send a message to the').' '.$to_msg,
                    'keywords' => self::$cfg->web_title,
                    'description' => self::$cfg->web_description,
                    'module' => 'sendmail'
                );
                // /member/sendmail.html
                $template = Template::create('member', 'member', 'sendmail');
                $template->add(array(
                    '/{TOPIC}/' => $index->topic,
                    '/{TOKEN}/' => $request->createToken(),
                    '/{RECIEVER}/' => $to_msg,
                    '/{SENDER}/' => $login['email'],
                    '/{RECIEVERID}/' => $to
                ));
                $index->detail = $template->render();
                // breadcrumbs
                $index->canonical = WEB_URL.'index.php?module=sendmail&to='.$to;
                Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
                return $index;
            }
        }
        // ไม่สามารถส่งอีเมลได้
        $message = Language::get('Unable to send e-mail, Because you can not send e-mail to yourself or can not find the email address of the recipient.');
        return createClass('Index\Error\Controller')->init('member', $message);
    }
}
