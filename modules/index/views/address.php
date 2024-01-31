<?php
/**
 * @filesource modules/index/views/address.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Address;

use Kotchasan\Country;
use Kotchasan\Http\Request;
use Kotchasan\Province;
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
     * แสดงรายละเอียดที่อยู่สมาชิก
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
            ->first('U.id', 'U.name', 'U.address1', 'U.address2', 'U.provinceID', 'U.zipcode', 'U.country', 'U.province');
        // member/address.html
        $template = Template::create('member', 'member', 'address');
        $contents = array();
        // ข้อมูลฟอร์ม
        foreach ($user as $key => $value) {
            if ($key === 'provinceID' || $key === 'country') {
                // select
                if ($key == 'provinceID') {
                    $source = Province::all();
                } elseif ($key == 'country') {
                    $source = Country::all();
                }
                $datas = array();
                foreach ($source as $k => $v) {
                    $sel = $k == $value ? ' selected' : '';
                    $datas[] = '<option value="'.$k.'"'.$sel.'>'.$v.'</option>';
                }
                $contents['/{'.strtoupper($key).'}/'] = implode('', $datas);
            } else {
                $contents['/{'.strtoupper($key).'}/'] = $value;
            }
        }
        $template->add($contents);
        $index->detail = $template->render();
        // คืนค่า
        return $index;
    }
}
