<?php
/**
 * @filesource modules/index/views/profile.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Profile;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Mime;
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
     * แสดงข้อมูลส่วนตัวสมาชิก
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
            ->first();
        $line_add_friend = !empty(self::$cfg->line_official_account) && !empty(self::$cfg->line_channel_access_token) && $user->social != 3;
        // member/profile.html
        $template = Template::create('member', 'member', 'profile');
        $contents = array(
            '/<NEWREGISTER>(.*)<\/NEWREGISTER>/isu' => $request->request('action')->toString() === 'newregister' ? '\\1' : '',
            '/<IDCARD>(.*)<\/IDCARD>/isu' => empty(self::$cfg->member_idcard) ? '' : '\\1',
            '/<LINE>(.*)<\/LINE>/isu' => $line_add_friend ? '\\1' : '',
            '/{ACCEPT}/' => Mime::getAccept(self::$cfg->user_icon_typies),
            '/{EDITPHONE}/' => in_array('phone1', self::$cfg->login_fields) ? 'disabled' : '',
            '/{EDITEMAIL}/' => in_array('email', self::$cfg->login_fields) || $user->social > 0 ? 'disabled' : ''
        );
        // ข้อมูลฟอร์ม
        foreach ($user as $key => $value) {
            if ($key == 'sex') {
                $datas = array();
                foreach (Language::get('SEXES') as $k => $v) {
                    $sel = $k == $value ? ' selected' : '';
                    $datas[] = '<option value="'.$k.'"'.$sel.'>'.$v.'</option>';
                }
                $contents['/{SEX}/'] = implode('', $datas);
            } elseif ($key === 'icon') {
                if (is_file(ROOT_PATH.self::$cfg->usericon_folder.$value)) {
                    $icon = WEB_URL.self::$cfg->usericon_folder.$value;
                } else {
                    $icon = WEB_URL.'skin/img/noicon.jpg';
                }
                $contents['/{ICON}/'] = $icon;
            } elseif ($key !== 'token') {
                $contents['/{'.strtoupper($key).'}/'] = $value;
            }
        }
        $template->add($contents);
        // after render
        Gcms::$view->setContentsAfter(array(
            '/:type/' => empty(self::$cfg->user_icon_typies) ? 'jpg' : implode(', ', (self::$cfg->user_icon_typies))
        ));
        $index->detail = $template->render();
        // คืนค่า
        return $index;
    }
}
