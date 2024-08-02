<?php
/**
 * @filesource modules/index/views/mods.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mods;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=mods
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * @ array
     */
    private $publisheds;

    /**
     * แสดงรายการโมดูลที่ติดตั้งแล้ว
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable([
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Index\Mods\Model',
            /* query where */
            'defaultFilters' => [
                ['index', 1]
            ],
            /* เรียงลำดับ */
            'sort' => 'owner,module,language',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => [$this, 'onRow'],
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => ['module_id', 'id', 'index'],
            /* ไม่แสดง checkbox */
            'hideCheckbox' => true,
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/model/mods/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => [
                [
                    'class' => 'button green icon-plus',
                    'href' => $uri->createBackUri(['module' => 'addmodule', 'id' => '0']),
                    'text' => '{LNG_Add New} {LNG_Module}'
                ]
            ],
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => [
                'topic' => [
                    'text' => '{LNG_Topic}'
                ],
                'published' => [
                    'text' => '{LNG_Status}',
                    'class' => 'center'
                ],
                'language' => [
                    'text' => '{LNG_Language}',
                    'class' => 'center'
                ],
                'module' => [
                    'text' => '{LNG_Module name}'
                ],
                'owner' => [
                    'text' => '&nbsp;'
                ],
                'last_update' => [
                    'text' => '{LNG_Last updated}',
                    'class' => 'center'
                ],
                'visited' => [
                    'text' => '{LNG_Preview}',
                    'class' => 'center'
                ]
            ],
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => [
                'published' => [
                    'class' => 'center'
                ],
                'language' => [
                    'class' => 'center'
                ],
                'last_update' => [
                    'class' => 'center'
                ],
                'visited' => [
                    'class' => 'visited'
                ]
            ],
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => [
                'edit' => [
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(['module' => 'pagewrite', 'id' => ':id']),
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
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        $item['language'] = empty($item['language']) ? '' : '<img src="'.WEB_URL.'language/'.$item['language'].'.gif" alt="'.$item['language'].'">';
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
