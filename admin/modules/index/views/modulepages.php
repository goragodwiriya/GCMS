<?php
/**
 * @filesource modules/index/views/modulepages.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Modulepages;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;

/**
 * module=modulepages
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * แสดงรายการ topic, description สำหรับหน้าเว็บย่อยต่างๆ
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Index\Modulepages\Model::toDataTable(),
            /* เรียงลำดับ */
            'sort' => 'module,page,language',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('menupages_perPage', 30)->toInt(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('module_id', 'id', 'owner'),
            /* ไม่แสดง checkbox */
            'hideCheckbox' => true,
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/model/modulepages/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'class' => 'button green icon-plus',
                    'href' => $uri->createBackUri(array('module' => 'modulepage', 'id' => '0')),
                    'text' => '{LNG_Add New} {LNG_Module page}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('topic', 'module', 'page'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'module' => array(
                    'text' => '{LNG_Module name}'
                ),
                'page' => array(
                    'text' => '{LNG_Webpage}'
                ),
                'topic' => array(
                    'text' => '{LNG_Topic}'
                ),
                'description' => array(
                    'text' => '{LNG_Description}'
                ),
                'language' => array(
                    'text' => '{LNG_Language}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'language' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'modulepage', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                ),
                'delete' => array(
                    'class' => 'icon-delete button red',
                    'id' => ':id',
                    'text' => '{LNG_Delete}'
                )
            )
        ));
        // save cookie
        setcookie('menupages_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['language'] = empty($item['language']) ? '' : '<img src="'.WEB_URL.'language/'.$item['language'].'.gif" alt="'.$item['language'].'">';
        return $item;
    }
}
