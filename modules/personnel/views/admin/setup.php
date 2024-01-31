<?php
/**
 * @filesource modules/personnel/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Personnel\Admin\Setup;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;

/**
 * module=personnel-setup
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
    private $categories;
    /**
     * @var mixed
     */
    private $uri;

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
        // หมวดหมู่
        $this->categories = \Index\Category\Model::categories((int) $index->module_id);
        // URL สำหรับส่งให้ตาราง
        $this->uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $this->uri,
            /* Model */
            'model' => 'Personnel\Admin\Setup\Model',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('personnel_perPage', 30)->toInt(),
            /* ฟิลด์ที่กำหนด (หากแตกต่างจาก Model) */
            'fields' => array(
                'id',
                'name',
                'picture',
                'category_id',
                'order',
                'email',
                'position',
                'phone',
                'module_id'
            ),
            /* query where */
            'defaultFilters' => array(
                array('module_id', (int) $index->module_id)
            ),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'module_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/personnel/model/admin/setup/action?mid='.$index->module_id,
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
                    'href' => $this->uri->createBackUri(array('module' => 'personnel-write', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_Personnel}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name', 'email', 'phone', 'position'),
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => array(
                'category_id' => array(
                    'name' => 'cat',
                    'text' => '{LNG_Personnel groups}',
                    'options' => array(0 => '{LNG_all items}') + $this->categories,
                    'default' => 0,
                    'value' => $request->request('cat')->toInt()
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'name' => array(
                    'text' => '{LNG_Name} {LNG_Surname}'
                ),
                'picture' => array(
                    'text' => '{LNG_Image}'
                ),
                'category_id' => array(
                    'text' => '{LNG_Category}',
                    'class' => 'center'
                ),
                'order' => array(
                    'text' => '{LNG_Sort}',
                    'class' => 'center'
                ),
                'email' => array(
                    'text' => '{LNG_Email}'
                ),
                'position' => array(
                    'text' => '{LNG_Position}'
                ),
                'phone' => array(
                    'text' => '{LNG_Phone}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'category_id' => array(
                    'class' => 'center'
                ),
                'phone' => array(
                    'class' => 'center'
                ),
                'order' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $this->uri->createBackUri(array('module' => 'personnel-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('personnel_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['phone'] = "<a href='tel:$item[phone]'>$item[phone]</a>";
        $item['email'] = "<a href='".$this->uri->createBackUri(array('module' => 'sendmail', 'to' => $item['email']))."'>$item[email]</a>";
        $thumb = is_file(ROOT_PATH.DATA_FOLDER.'personnel/'.$item['picture']) ? WEB_URL.DATA_FOLDER.'personnel/'.$item['picture'] : '../modules/personnel/img/noimage.jpg';
        $item['picture'] = '<img src="'.$thumb.'" style="max-height:50px" alt=thumbnail>';
        $item['category_id'] = empty($item['category_id']) || empty($this->categories[$item['category_id']]) ? '{LNG_Uncategorized}' : $this->categories[$item['category_id']];
        $item['order'] = '<label><input type=text size=5 id=order_'.$item['id'].' value="'.$item['order'].'"></label>';
        return $item;
    }
}
