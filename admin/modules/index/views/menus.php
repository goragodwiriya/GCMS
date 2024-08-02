<?php
/**
 * @filesource modules/index/view/menus.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Menus;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=menus
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
    private $publisheds;

    /**
     * รายการเมนู
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->publisheds = Language::get('MENU_PUBLISHEDS');
        // menu ที่เลือก default คือ MAINMENU
        $parent = $request->request('parent')->toString();
        $installed_menus = Language::get('MENU_PARENTS');
        $menus = array_keys($installed_menus);
        $parent = in_array($parent, $menus) ? $parent : reset($menus);
        $this->toplvl = -1;
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable([
            /* Uri */
            'uri' => $uri,
            /* model */
            'model' => 'Index\Menus\Model',
            /* เรียงลำดับ */
            'sort' => 'menu_order',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => [$this, 'onRow'],
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => ['id', 'index_id', 'level', 'menu_url', 'ilanguage', 'parent', 'menu_order'],
            /* ไม่แสดง checkbox */
            'hideCheckbox' => true,
            /* enable drag row */
            'dragColumn' => 4,
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/model/menus/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => [
                [
                    'class' => 'button green icon-plus',
                    'href' => $uri->createBackUri(['module' => 'menuwrite', 'id' => '0']),
                    'text' => '{LNG_Add New} {LNG_Menu}'
                ]
            ],
            /* ฟิลเตอร์ของตาราง */
            'filters' => [
                'parent' => [
                    'name' => 'parent',
                    'text' => '{LNG_Choose}',
                    'options' => $installed_menus,
                    'value' => $parent
                ]
            ],
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => [
                'menu_text' => [
                    'text' => '{LNG_Menu}'
                ],
                'move_left' => [
                    'text' => ''
                ],
                'move_right' => [
                    'text' => ''
                ],
                'alias' => [
                    'text' => '{LNG_Alias}'
                ],
                'published' => [
                    'text' => '{LNG_Status}',
                    'class' => 'center'
                ],
                'language' => [
                    'text' => '{LNG_Language}',
                    'class' => 'center'
                ],
                'menu_tooltip' => [
                    'text' => '{LNG_Tooltip}'
                ],
                'accesskey' => [
                    'text' => '{LNG_Accesskey}',
                    'class' => 'center'
                ],
                'module' => [
                    'text' => '{LNG_Link}/{LNG_Module}'
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
                'accesskey' => [
                    'class' => 'center'
                ]
            ],
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => [
                'edit' => [
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(['module' => 'menuwrite', 'id' => ':id']),
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
        $text = '';
        for ($i = 0; $i < $item['level']; ++$i) {
            $text .= '&nbsp;&nbsp;&nbsp;';
        }
        $item['menu_text'] = (empty($text) ? '' : $text.'↳&nbsp;').$item['menu_text'];
        $item['move_left'] = '<a id=move_left_'.$item['move_left'].' title="{LNG_Move submenu to the top}" class='.($item['level'] == 0 ? 'hidden' : 'icon-move_left').'></a>';
        $item['move_right'] = '<a id=move_right_'.$item['move_right'].' title="{LNG_Move menu to submenu of the top}" class='.($item['level'] > $this->toplvl ? 'hidden' : 'icon-move_right').'></a>';
        $item['published'] = $this->publisheds[$item['published']];
        $item['language'] = empty($item['language']) ? '' : '<img src="'.WEB_URL.'language/'.$item['language'].'.gif" alt="'.$item['language'].'">';
        if (empty($item['index_id'])) {
            $item['module'] = str_replace(['{', '}'], ['&#x007B;', '&#x007D;'], $item['menu_url']);
        } else {
            $item['module'] .= empty($item['ilanguage']) ? '' : '&nbsp;<img src="'.WEB_URL.'language/'.$item['ilanguage'].'.gif" alt="'.$item['ilanguage'].'">';
        }
        $this->toplvl = $item['level'];
        return $item;
    }
}
