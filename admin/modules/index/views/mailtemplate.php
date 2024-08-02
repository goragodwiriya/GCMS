<?php
/**
 * @filesource modules/index/views/mailtemplate.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mailtemplate;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;

/**
 * module=mailtemplate
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ตารางแม่แบบอีเมล
     *
     * @param Request $request
     *
     * @return type
     */
    public function render(Request $request)
    {
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable([
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Index\Mailtemplate\Model',
            /* เรียงลำดับ */
            'sort' => 'module,email_id,language',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => [$this, 'onRow'],
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => ['id', 'email_id', 'subject'],
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/model/mailtemplate/action',
            'actionCallback' => 'dataTableActionCallback',
            'actionConfirm' => 'confirmAction',
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => [$this, 'onCreateButton'],
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => [
                'name' => [
                    'text' => '{LNG_Name}'
                ],
                'language' => [
                    'text' => '{LNG_Language}',
                    'class' => 'center'
                ],
                'module' => [
                    'text' => '{LNG_Module}',
                    'class' => 'center'
                ]
            ],
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => [
                'language' => [
                    'class' => 'center'
                ],
                'module' => [
                    'class' => 'center'
                ]
            ],
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => [
                'edit' => [
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(['module' => 'mailwrite', 'id' => ':id']),
                    'text' => '{LNG_Edit}'
                ],
                'delete' => [
                    'class' => 'icon-delete button red',
                    'id' => ':id',
                    'text' => '{LNG_Delete}'
                ]
            ]
        ]);
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
        $item['name'] = $item['module'] == 'mailmerge' ? $item['subject'] : $item['name'];
        $item['language'] = empty($item['language']) ? '' : '<img src="'.WEB_URL.'language/'.$item['language'].'.gif" alt="'.$item['language'].'">';
        return $item;
    }

    /**
     * ฟังก์ชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param $btn        string id ของ button
     * @param $attributes array  property ของปุ่ม
     * @param $item      array  ข้อมูลในแถว
     */
    public function onCreateButton($btn, $attributes, $item)
    {
        return $btn != 'delete' || $item['email_id'] == 0 ? $attributes : false;
    }
}
