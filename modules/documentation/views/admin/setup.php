<?php
/**
 * @filesource modules/documentation/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Admin\Setup;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=documentation-setup
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
    private $index;
    /**
     * @var mixed
     */
    private $publisheds;
    /**
     * @var mixed
     */
    private $categories;

    /**
     * ตารางรายการ
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
        $this->categories = \Index\Category\Model::categories((int) $index->module_id);
        $category_id = $request->request('cat', 0)->toInt();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Documentation\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('documentation_perPage', 30)->toInt(),
            /* query where */
            'defaultFilters' => array(
                array('module_id', (int) $index->module_id),
                array('index', 0),
                array('language', array(Language::name(), ''))
            ),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('member_id', 'id', 'status', 'module_id', 'index', 'language'),
            /* enable drag row */
            'dragColumn' => 1,
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/documentation/model/admin/setup/action?mid='.$index->module_id,
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
                    'href' => $uri->createBackUri(array('module' => 'documentation-write', 'mid' => $index->module_id, 'cat' => $category_id)),
                    'text' => '{LNG_Add New} {LNG_Content}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('topic', 'detail'),
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => array(
                'category_id' => array(
                    'name' => 'cat',
                    'text' => '{LNG_Category}',
                    'options' => array(0 => '{LNG_all items}') + $this->categories,
                    'default' => 0,
                    'value' => $category_id
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'topic' => array(
                    'text' => '{LNG_Topic}'
                ),
                'published' => array(
                    'text' => ''
                ),
                'category_id' => array(
                    'text' => '{LNG_Category}',
                    'class' => 'center'
                ),
                'writer' => array(
                    'text' => '{LNG_Writer}'
                ),
                'last_update' => array(
                    'text' => '{LNG_Last updated}',
                    'class' => 'center'
                ),
                'visited' => array(
                    'text' => '{LNG_Viewing}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'published' => array(
                    'class' => 'center'
                ),
                'category_id' => array(
                    'class' => 'center'
                ),
                'last_update' => array(
                    'class' => 'center date'
                ),
                'visited' => array(
                    'class' => 'visited center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'documentation-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('documentation_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['topic'] = '<a href="../index.php?module='.$this->index->module.'&amp;id='.$item['id'].'" target=_blank>'.$item['topic'].'</a>';
        $item['category_id'] = empty($item['category_id']) || empty($this->categories[$item['category_id']]) ? '{LNG_Uncategorized}' : $this->categories[$item['category_id']];
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        $item['writer'] = '<span class="status'.$item['status'].'">'.$item['writer'].'</span>';
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
