<?php
/**
 * @filesource modules/index/views/password.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Password;

use Kotchasan\Http\Request;
use Kotchasan\Template;

/**
 * หน้าแก้ไขข้อมูลส่วนตัว
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มแก้ไขรหัสผ่านสมาชิก
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function render(Request $request, $index)
    {
        // อ่านข้อมูลสมาชิก
        $user = \Kotchasan\Model::createQuery()
            ->from('user U')
            ->where(array('U.id', (int) $_SESSION['login']['id']))
            ->first('U.id');
        // member/password.html
        $template = Template::create('member', 'member', 'password');
        $contents = array(
            '/{ID}/' => $user->id
        );
        $template->add($contents);
        $index->detail = $template->render();
        // คืนค่า
        return $index;
    }
}
