<?php
/**
 * @filesource modules/index/views/upgrade2.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade2;

use Kotchasan\Http\Request;

/**
 * ติดตั้ง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * step 2
     *
     * @return string
     */
    public function render(Request $request, $error = '')
    {
        $content = array();
        if (defined('INSTALL')) {
            $password = $request->session('password', 'admin')->topic();
            $email = $request->session('email', 'admin@'.$request->getUri()->getHost())->url();
            $content[] = '<form method=post action=index.php autocomplete=off>';
            $content[] = '<h2>{TITLE}</h2>';
            $content[] = '<p>กรอกข้อมูลส่วนตัวของผู้ดูแลระบบ สำหรับใช้ในการเข้าสู่ระบบ</p>';
            if (!empty($error)) {
                $content[] = '<p class=warning>'.$error.'</p>';
            }
            $content[] = '<p class=item><label for=email>ที่อยู่อีเมล</label><span class="g-input icon-email"><input type=email size=70 id=email name=email maxlength=255 value="'.$email.'" autofocus required></span></p>';
            $content[] = '<p class="comment">ที่อยู่อีเมลสำหรับผู้ดูแลระบบสูงสุด ใช้ในการการเข้าระบบ</p>';
            $content[] = '<p class=item><label for=password>รหัสผ่าน</label><span class="g-input icon-password"><input type=password size=70 id=password name=password maxlength=20 value="'.$password.'" required></span></p>';
            $content[] = '<p class="row comment">ภาษาอังกฤษตัวพิมพ์เล็กและตัวเลข 4-8 หลัก</p>';
            $content[] = '<input type=hidden name=step value=3>';
            $content[] = '<p><input class="button large save" type=submit value="ดำเนินการต่อ"></p>';
            $content[] = '</form>';
        }
        return (object) array(
            'title' => 'ข้อมูลสมาชิกผู้ดูแลระบบ',
            'content' => implode('', $content)
        );
    }
}
