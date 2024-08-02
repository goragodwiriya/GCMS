<?php
/**
 * @filesource Widgets/Textlink/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlink\Views;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Gcms\Adminview
{
    /**
     * @var array
     */
    private $publisheds;

    /**
     * module=Textlink-settings
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        // name
        $typies = ['' => '{LNG_all items}'];
        $actions = [];
        foreach (Language::get('PUBLISHEDS') as $key => $value) {
            $actions['published_'.$key] = $value;
        }
        $actions['delete'] = '{LNG_Delete}';
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable([
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Widgets\Textlink\Models\Settings',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => [$this, 'onRow'],
            /* รายชื่อฟิลด์ที่ query (ถ้าแตกต่างจาก Model) */
            'fields' => [
                'id',
                'name',
                'description',
                'published',
                'type',
                'url',
                'text',
                'width',
                'height',
                'publish_start',
                'publish_end',
                'link_order'
            ],
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => ['id', 'type', 'height', 'link_order'],
            /* เรียงลำดับ */
            'sort' => 'name, link_order ASC',
            /* enable drag row */
            'dragColumn' => 1,
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/Widgets/Textlink/Models/Action/get',
            'actionCallback' => 'dataTableActionCallback',
            'actionConfirm' => 'confirmAction',
            'actions' => [
                [
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => $actions
                ],
                [
                    'class' => 'button green icon-plus',
                    'href' => $uri->createBackUri(['module' => 'Textlink-write']),
                    'text' => '{LNG_Add New} {LNG_Text links}'
                ]
            ],
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => [
                'name' => [
                    'name' => 'name',
                    'text' => '{LNG_Type of link}',
                    'options' => \Widgets\Textlink\Models\Index::getTypies(),
                    'default' => '',
                    'value' => $request->request('name')->topic()
                ]
            ],
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => [
                'name' => [
                    'text' => '{LNG_Name}'
                ],
                'description' => [
                    'text' => '{LNG_Description} ({LNG_Type})'
                ],
                'url' => [
                    'text' => '{LNG_URL}'
                ],
                'text' => [
                    'text' => '{LNG_message}'
                ],
                'width' => [
                    'text' => '{LNG_Size of} {LNG_Image}',
                    'class' => 'center'
                ],
                'publish_start' => [
                    'text' => '{LNG_Published date}',
                    'class' => 'center'
                ],
                'publish_end' => [
                    'text' => '{LNG_Published close}',
                    'class' => 'center'
                ],
                'published' => [
                    'text' => ''
                ]
            ],
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => [
                'published' => [
                    'class' => 'center'
                ],
                'width' => [
                    'class' => 'center'
                ],
                'publish_start' => [
                    'class' => 'center'
                ],
                'publish_end' => [
                    'class' => 'center'
                ]
            ],
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => [
                'edit' => [
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(['module' => 'Textlink-write', 'id' => ':id']),
                    'text' => '{LNG_Edit}'
                ]
            ]
        ]);
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
        $item['url'] = '<a href="'.$item['url'].'" target=_blank>'.str_replace(['{', '}'], ['&#x007B;', '&#x007D;'], $item['url']).'</a>';
        $item['description'] = $item['description'].' ('.$item['type'].')';
        $item['width'] = $item['width'].' * '.$item['height'];
        $item['publish_start'] = Date::format($item['publish_start'], 'd M Y');
        $item['publish_end'] = $item['publish_end'] == 0 ? '{LNG_Dateless}' : Date::format($item['publish_end'], 'd M Y');
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
