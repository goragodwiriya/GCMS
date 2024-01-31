<?php
/**
 * @filesource modules/gallery/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Admin\Setup;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=gallery-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * @var array
     */
    private $thumbnails;
    /**
     * @var string
     */
    private $module;

    /**
     * แสดงรายการอัลบัม
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        $this->module = $index->module;
        $this->thumbnails = Language::get('THUMBNAILS');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Gallery\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('album_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('album_sort', 'id desc')->toString(),
            /* query where */
            'defaultFilters' => array(
                array('module_id', (int) $index->module_id)
            ),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('member_id', 'status', 'module_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/gallery/model/admin/setup/action?mid='.$index->module_id,
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
                    'href' => $uri->createBackUri(array('module' => 'gallery-write', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_Album}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('topic', 'detail'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'id' => array(
                    'text' => '{LNG_ID}',
                    'sort' => 'id'
                ),
                'topic' => array(
                    'text' => '{LNG_Album}',
                    'sort' => 'topic'
                ),
                'image' => array(
                    'text' => '{LNG_Image}'
                ),
                'count' => array(
                    'text' => '{LNG_Number}',
                    'class' => 'center'
                ),
                'visited' => array(
                    'text' => '{LNG_Preview}',
                    'class' => 'center'
                ),
                'last_update' => array(
                    'text' => '{LNG_date}',
                    'class' => 'center',
                    'sort' => 'last_update'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'count' => array(
                    'class' => 'center color-red'
                ),
                'visited' => array(
                    'class' => 'center no'
                ),
                'last_update' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'gallery-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                ),
                'upload' => array(
                    'class' => 'icon-image button orange',
                    'href' => $uri->createBackUri(array('module' => 'gallery-upload', 'id' => ':id')),
                    'text' => '{LNG_Upload}'
                )
            )
        ));
        // save cookie
        setcookie('album_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('album_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['topic'] = '<a href="'.WEB_URL.'index.php?module='.$this->module.'&amp;id='.$item['id'].'" target=_blank>'.$item['topic'].'</a>';
        if (is_file(ROOT_PATH.DATA_FOLDER.'gallery/'.$item['id'].'/'.$item['image'])) {
            $item['image'] = '<img src="'.WEB_URL.DATA_FOLDER.'gallery/'.$item['id'].'/thumb_'.$item['image'].'" title="'.$this->thumbnails[1].'" style="max-width:50px;max-height:50px" alt=thumbnail>';
        } else {
            $item['image'] = '<span class=icon-thumbnail title="'.$this->thumbnails[0].'"></span>';
        }
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        return $item;
    }
}
