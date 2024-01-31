<?php
/**
 * @filesource modules/edocument/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Admin\Setup;

use Gcms\Gcms;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Text;

/**
 * module=edocument-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * แสดงรายการเอกสาร
     *
     * @param Request $request
     * @param object  $index
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $index, $login)
    {
        $where = array(array('module_id', (int) $index->module_id));
        if (!Gcms::canConfig($login, $index, 'moderator')) {
            $where[] = array('sender_id', (int) $login['id']);
        }
        // model
        $model = new \Kotchasan\Model();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Edocument\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('edocument_perPage', 30)->toInt(),
            /* query where */
            'defaultFilters' => $where,
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'ext', 'file', 'module_id', 'sender_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/edocument/model/admin/setup/action?mid='.$index->module_id,
            'actionCallback' => 'dataTableActionCallback',
            'actionConfirm' => 'confirmAction',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}'
                    )
                ),
                array(
                    'class' => 'button green icon-plus',
                    'href' => $uri->createBackUri(array('module' => 'edocument-write', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_E-Document}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('topic', 'document_no', 'detail'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'document_no' => array(
                    'text' => '{LNG_Document number}'
                ),
                'topic' => array(
                    'text' => '{LNG_File Name}'
                ),
                'detail' => array(
                    'text' => '{LNG_Description}'
                ),
                'sender' => array(
                    'text' => '{LNG_Sender}',
                    'class' => 'center'
                ),
                'size' => array(
                    'text' => '{LNG_File size}',
                    'class' => 'center'
                ),
                'last_update' => array(
                    'text' => '{LNG_Last updated}',
                    'class' => 'center'
                ),
                'downloads' => array(
                    'text' => '{LNG_Download}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'sender' => array(
                    'class' => 'center'
                ),
                'size' => array(
                    'class' => 'center'
                ),
                'last_update' => array(
                    'class' => 'center date'
                ),
                'downloads' => array(
                    'class' => 'center visited'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'edocument-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('edocument_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['topic'] = '<a href="'.WEB_URL.DATA_FOLDER.'edocument/'.$item['file'].'" target=_blank>'.$item['topic'].'.'.$item['ext'].'</a>';
        $item['size'] = Text::formatFileSize($item['size']);
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        return $item;
    }
}
