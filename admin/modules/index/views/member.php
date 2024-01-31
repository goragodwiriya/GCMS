<?php
/**
 * @filesource modules/index/views/member.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Member;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=member
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * @var mixed
     */
    private $sexes;

    /**
     * ตารางรายชื่อสมาชิก
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->sexes = Language::get('SEXES');
        // สถานะสมาชิก
        $change_member_status = array();
        $member_status = array(-1 => '{LNG_all items}');
        foreach (self::$cfg->member_status as $key => $value) {
            $member_status[$key] = $value;
            $change_member_status[$key] = '{LNG_Change member status to} '.$value;
        }
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Index\Member\Model::toDataTable(),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('member_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('member_sort', 'id desc')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('visited', 'status', 'activatecode', 'website'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name', 'displayname', 'email', 'phone1'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/model/member/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'accept' => '{LNG_Accept membership}',
                        'activate' => '{LNG_Send confirmation email}',
                        'sendpassword' => '{LNG_Get new password}',
                        'ban_1' => '{LNG_Suspended}',
                        'ban_0' => '{LNG_Cancel suspension}',
                        'active_1' => '{LNG_Access to the system administrator.}',
                        'active_0' => '{LNG_Unable to login}',
                        'delete' => '{LNG_Delete}'
                    )
                ),
                array(
                    'id' => 'status',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => $change_member_status
                )
            ),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                'status' => array(
                    'name' => 'status',
                    'default' => -1,
                    'text' => '{LNG_Member status}',
                    'options' => $member_status,
                    'value' => $request->request('status', -1)->toInt()
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'id' => array(
                    'text' => '{LNG_ID}',
                    'sort' => 'id'
                ),
                'ban' => array(
                    'text' => '',
                    'colspan' => 3
                ),
                'email' => array(
                    'text' => '{LNG_Email}',
                    'sort' => 'email'
                ),
                'name' => array(
                    'text' => '{LNG_Name} {LNG_Surname}',
                    'sort' => 'name'
                ),
                'displayname' => array(
                    'text' => '{LNG_Displayname}',
                    'sort' => 'displayname'
                ),
                'phone1' => array(
                    'text' => '{LNG_Phone}'
                ),
                'create_date' => array(
                    'text' => '{LNG_Created}',
                    'class' => 'center'
                ),
                'lastvisited' => array(
                    'text' => '{LNG_Last login} ({LNG_times})',
                    'class' => 'center',
                    'sort' => 'lastvisited'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'active' => array(
                    'class' => 'center'
                ),
                'social' => array(
                    'class' => 'center'
                ),
                'phone' => array(
                    'class' => 'center'
                ),
                'status' => array(
                    'class' => 'center'
                ),
                'create_date' => array(
                    'class' => 'center'
                ),
                'lastvisited' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'editprofile', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('member_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('member_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        $item['email'] = '<a href="index.php?module=sendmail&to='.$item['email'].'" class="status'.$item['status'].'">'.$item['email'].'</a>';
        $item['create_date'] = Date::format($item['create_date'], 'd M Y');
        $item['lastvisited'] = empty($item['lastvisited']) ? '' : Date::format($item['lastvisited'], 'd M Y H:i').' ('.number_format($item['visited']).')';
        $item['ban'] = $item['ban'] == 1 ? '<span class="icon-ban ban" title="{LNG_Members were suspended}"></span>' : '<span class="icon-ban"></span>';
        if ($item['social'] == 1) {
            $item['social'] = '<span class="icon-facebook"></span>';
        } elseif ($item['social'] == 2) {
            $item['social'] = '<span class="icon-google"></span>';
        } else {
            $item['social'] = '';
        }
        $item['phone1'] = empty($item['phone1']) ? '' : '<a href="tel:'.$item['phone1'].'">'.$item['phone1'].'</a>';
        $item['active'] = $item['active'] == 1 ? '<span class="icon-valid access" title="{LNG_Access to the system administrator.}"></span>' : '<span class="icon-valid disabled"></span>';
        $item['name'] = empty($item['website']) ? $item['name'] : '<a href="'.$item['website'].'" target="_blank">'.$item['name'].'</a>';
        return $item;
    }
}
