<?php
/**
 * @filesource event/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Event\Admin\Setup;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=event-setup
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
    private $publisheds;
    /**
     * @var mixed
     */
    private $module;

    /**
     * แสดงรายการอีเว้นต์
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        $this->module = $index->module;
        $this->publisheds = Language::get('PUBLISHEDS');
        // model
        $model = new \Kotchasan\Model();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Event\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('event_perPage', 30)->toInt(),
            /* query where */
            'defaultFilters' => array(
                array('module_id', (int) $index->module_id)
            ),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'end_date', 'color', 'module_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/event/model/admin/setup/action?mid='.$index->module_id,
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
                    'href' => $uri->createBackUri(array('module' => 'event-write', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_Event}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('topic', 'detail'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'topic' => array(
                    'text' => '{LNG_Topic}'
                ),
                'begin_date' => array(
                    'text' => '{LNG_Date and time of event}',
                    'class' => 'center'
                ),
                'published' => array(
                    'text' => '{LNG_Published}',
                    'class' => 'center'
                ),
                'last_update' => array(
                    'text' => '{LNG_Last updated}',
                    'class' => 'center'
                ),
                'writer' => array(
                    'text' => '{LNG_Writer}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'published' => array(
                    'class' => 'center'
                ),
                'begin_date' => array(
                    'class' => 'center date'
                ),
                'last_update' => array(
                    'class' => 'center date'
                ),
                'writer' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'event-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('event_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        $item['topic'] = '<span class=event_color style="background-color:'.$item['color'].'"></span><a href="../index.php?module='.$this->module.'&amp;id='.$item['id'].'" target=_blank>'.$item['topic'].'</a>';
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        $item['begin_date'] = Date::format($item['begin_date'], 'd M Y H:i').($item['end_date'] === '0000-00-00 00:00:00' ? '' : Date::format($item['end_date'], ' - H:i'));
        return $item;
    }
}
