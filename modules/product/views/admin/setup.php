<?php
/**
 * @filesource modules/product/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Admin\Setup;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=product-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * @var object
     */
    private $index;
    /**
     * @var array
     */
    private $publisheds;

    /**
     * ตาราง สินค้า
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        $this->index = $index;
        $this->publisheds = Language::get('PUBLISHEDS');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Product\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('product_perPage', 30)->toInt(),
            /* query where */
            'defaultFilters' => array(
                array('module_id', (int) $index->module_id)
            ),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'module_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/product/model/admin/setup/action?mid='.$index->module_id,
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
                    'href' => $uri->createBackUri(array('module' => 'product-write', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_Product}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('topic', 'detail'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'product_no' => array(
                    'text' => '{LNG_Product Code}'
                ),
                'topic' => array(
                    'text' => '{LNG_Topic}'
                ),
                'published' => array(
                    'text' => '',
                    'class' => 'center'
                ),
                'last_update' => array(
                    'text' => '{LNG_Last updated}',
                    'class' => 'center'
                ),
                'visited' => array(
                    'text' => '{LNG_Preview}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'published' => array(
                    'class' => 'center'
                ),
                'visited' => array(
                    'class' => 'center'
                ),
                'last_update' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'product-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('product_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['topic'] = '<a href="../index.php?module='.$this->index->module.'&amp;id='.$item['id'].'" target=preview>'.$item['topic'].'</a>';
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
