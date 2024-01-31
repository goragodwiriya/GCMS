<?php
/**
 * @filesource Widgets/Rss/Views/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Rss\Views;

use Kotchasan\DataTable;
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
     * @var mixed
     */
    private $publisheds;

    /**
     * module=Rss-settings
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        // name
        $typies = array('' => '{LNG_all items}');
        $actions = array();
        foreach (Language::get('PUBLISHEDS') as $key => $value) {
            $actions['published_'.$key] = $value;
        }
        $actions['delete'] = '{LNG_Delete}';
        // ตาราง
        if (isset(self::$cfg->rss_tabs)) {
            foreach (self::$cfg->rss_tabs as $k => $vs) {
                self::$cfg->rss_tabs[$k]['id'] = $k;
            }
        } else {
            self::$cfg->rss_tabs = array();
        }
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* datas (Array) */
            'datas' => self::$cfg->rss_tabs,
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'cols'),
            /* enable drag row */
            'dragColumn' => 1,
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/Widgets/Rss/Models/Action/get',
            'actionCallback' => 'dataTableActionCallback',
            'actionConfirm' => 'confirmAction',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array('delete' => '{LNG_Delete}')
                ),
                array(
                    'class' => 'button green icon-plus',
                    'href' => $uri->createBackUri(array('module' => 'Rss-write')),
                    'text' => '{LNG_Add New} {LNG_RSS Tab}'
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'url' => array(
                    'text' => '{LNG_URL}'
                ),
                'topic' => array(
                    'text' => '{LNG_Topic}'
                ),
                'index' => array(
                    'text' => '{LNG_ID}',
                    'class' => 'center'
                ),
                'rows' => array(
                    'text' => '{LNG_Rows} * {LNG_Cols}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'index' => array(
                    'class' => 'center'
                ),
                'rows' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'Rss-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
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
        $item['url'] = '<a href="'.$item['url'].'" target=_blank>'.$item['url'].'</a>';
        $item['rows'] = $item['rows'].' * '.$item['cols'];
        return $item;
    }
}
