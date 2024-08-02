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
        $table = new DataTable([
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Gallery\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('album_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('album_sort', 'id desc')->toString(),
            /* query where */
            'defaultFilters' => [
                ['module_id', (int) $index->module_id]
            ],
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => [$this, 'onRow'],
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => ['member_id', 'status', 'module_id'],
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/gallery/model/admin/setup/action?mid='.$index->module_id,
            'actionCallback' => 'dataTableActionCallback',
            'actionConfirm' => 'confirmAction',
            'actions' => [
                [
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => [
                        'delete' => '{LNG_Delete}'
                    ]
                ]
            ],
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => ['topic', 'detail'],
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => [
                'id' => [
                    'text' => '{LNG_ID}',
                    'sort' => 'id'
                ],
                'topic' => [
                    'text' => '{LNG_Album}',
                    'sort' => 'topic'
                ],
                'image' => [
                    'text' => '{LNG_Image}'
                ],
                'count' => [
                    'text' => '{LNG_Number}',
                    'class' => 'center'
                ],
                'visited' => [
                    'text' => '{LNG_Preview}',
                    'class' => 'center'
                ],
                'last_update' => [
                    'text' => '{LNG_date}',
                    'class' => 'center',
                    'sort' => 'last_update'
                ]
            ],
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => [
                'count' => [
                    'class' => 'center color-red'
                ],
                'visited' => [
                    'class' => 'center no'
                ],
                'last_update' => [
                    'class' => 'center'
                ]
            ],
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => [
                'edit' => [
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(['module' => 'gallery-write', 'id' => ':id']),
                    'text' => '{LNG_Edit}'
                ],
                'upload' => [
                    'class' => 'icon-image button orange',
                    'href' => $uri->createBackUri(['module' => 'gallery-upload', 'id' => ':id']),
                    'text' => '{LNG_Upload}'
                ]
            ],
            /* ปุ่มเพิ่ม */
            'addNew' => [
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(['module' => 'gallery-write', 'mid' => $index->module_id]),
                'title' => '{LNG_Add New} {LNG_Album}'
            ]
        ]);
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
