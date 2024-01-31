<?php
/**
 * @filesource modules/download/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Admin\Setup;

use Gcms\Gcms;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Text;

/**
 * module=download-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * ข้อมูลโมดูล
     */
    private $categories;

    /**
     * ตารางรายการ
     *
     * @param Request $request
     * @param object  $index
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $index, $login)
    {
        // หมวดหมู่
        $this->categories = \Index\Category\Model::categories((int) $index->module_id);
        $where = array(array('module_id', (int) $index->module_id));
        if (!Gcms::canConfig($login, $index, 'moderator')) {
            $where[] = array('member_id', (int) $login['id']);
        }
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Download\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('download_perPage', 30)->toInt(),
            /* ฟิลด์ที่กำหนด (หากแตกต่างจาก Model) */
            'fields' => array(
                'name',
                'id',
                'ext',
                'category_id',
                'detail',
                'size',
                'last_update',
                'downloads',
                'file',
                'module_id',
                'member_id'
            ),
            /* query where */
            'defaultFilters' => $where,
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('ext', 'file', 'module_id', 'member_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/download/model/admin/setup/action?mid='.$index->module_id,
            'actionCallback' => 'dataTableActionCallback',
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
                    'href' => $uri->createBackUri(array('module' => 'download-write', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_Download file}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name', 'ext', 'detail', 'file'),
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => array(
                'category_id' => array(
                    'name' => 'cat',
                    'text' => '{LNG_Category}',
                    'options' => array(0 => '{LNG_all items}') + $this->categories,
                    'default' => 0,
                    'value' => $request->request('cat')->toInt()
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'name' => array(
                    'text' => '{LNG_File Name}',
                    'sort' => 'name'
                ),
                'id' => array(
                    'text' => '{LNG_Widget}',
                    'sort' => 'id'
                ),
                'category_id' => array(
                    'text' => '{LNG_Category}',
                    'class' => 'center',
                    'sort' => 'category_id'
                ),
                'detail' => array(
                    'text' => '{LNG_Description}'
                ),
                'size' => array(
                    'text' => '{LNG_File size}',
                    'class' => 'center',
                    'sort' => 'size'
                ),
                'last_update' => array(
                    'text' => '{LNG_Last updated}',
                    'class' => 'center',
                    'sort' => 'last_update'
                ),
                'downloads' => array(
                    'text' => '{LNG_Download}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'category_id' => array(
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
                    'href' => $uri->createBackUri(array('module' => 'download-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('download_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['id'] = '<em>{WIDGET_DOWNLOAD_'.$item['id'].'}</em>';
        $item['name'] = "<a href='".WEB_URL."$item[file]' target=_blank>$item[name].$item[ext]</a>";
        $item['size'] = is_file(ROOT_PATH.$item['file']) ? Text::formatFileSize($item['size']) : '<em>0</em>';
        $item['category_id'] = empty($item['category_id']) || empty($this->categories[$item['category_id']]) ? '{LNG_Uncategorized}' : $this->categories[$item['category_id']];
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        return $item;
    }
}
