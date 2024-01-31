<?php
/**
 * @filesource modules/video/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Video\Admin\Setup;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=video-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * แสดงรายการ Video
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Video\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('video_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => 'id DESC',
            /* ฟิลด์ที่กำหนด (หากแตกต่างจาก Model) */
            'fields' => array(
                'id',
                'youtube thumbnail',
                'topic',
                'youtube',
                'last_update',
                'views'
            ),
            /* query where */
            'defaultFilters' => array(
                array('module_id', (int) $index->module_id)
            ),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/video/model/admin/setup/action?mid='.$index->module_id,
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
                    'href' => $uri->createBackUri(array('module' => 'video-write', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_Video}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('description', 'topic', 'youtube'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'id' => array(
                    'text' => '{LNG_ID}'
                ),
                'thumbnail' => array(
                    'text' => '{LNG_Thumbnail}'
                ),
                'topic' => array(
                    'text' => '{LNG_Topic}'
                ),
                'youtube' => array(
                    'text' => '{LNG_Youtube ID}',
                    'class' => 'center'
                ),
                'last_update' => array(
                    'text' => '{LNG_Last updated}',
                    'class' => 'center'
                ),
                'views' => array(
                    'text' => '{LNG_Views}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'youtube' => array(
                    'class' => 'center'
                ),
                'last_update' => array(
                    'class' => 'center date'
                ),
                'views' => array(
                    'class' => 'center visited'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'video-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('video_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $thumb = is_file(ROOT_PATH.DATA_FOLDER.'video/'.$item['youtube'].'.jpg') ? WEB_URL.DATA_FOLDER.'video/'.$item['youtube'].'.jpg' : '../modules/video/img/nopicture.jpg';
        $item['thumbnail'] = '<img src="'.$thumb.'" style="max-height:50px" alt=thumbnail>';
        $item['youtube'] = '<a href="http://www.youtube.com/watch?v='.$item['youtube'].'" target=_blank>'.$item['youtube'].'</a>';
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        return $item;
    }
}
