<?php
/**
 * @filesource modules/index/controllers/sendmail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Sendmail;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=sendmail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มส่งอีเมลจากแอดมิน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Send email by Admin');
        // เลือกเมนู
        $this->menu = 'email';
        // แอดมิน
        if ($login = Login::adminAccess()) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-email">{LNG_Mailbox}</span></li>');
            $ul->appendChild('<li><span>{LNG_Email send}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-email-sent">'.$this->title.'</h2>'
            ));
            // แสดงฟอร์ม
            $section->appendChild(\Index\Sendmail\View::create()->render($request, $login));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
