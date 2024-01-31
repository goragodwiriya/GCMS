<?php
/**
 * @filesource modules/portfolio/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Portfolio\Admin\Setup;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=portfolio-setup
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
    private $thumbnails;

    /**
     * แสดงรายการ Portfolio
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        $this->thumbnails = Language::get('THUMBNAILS');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Portfolio\Admin\Setup\Model',
            /* ฟิลด์ที่กำหนด (หากแตกต่างจาก Model) */
            'fields' => array(
                'title',
                'image',
                'published',
                'url',
                'create_date',
                'visited',
                'id',
                'module_id'
            ),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('portfolio_perPage', 30)->toInt(),
            /* query where */
            'defaultFilters' => array(
                array('module_id', (int) $index->module_id)
            ),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'module_id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/portfolio/model/admin/setup/action?mid='.$index->module_id,
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
                    'href' => $uri->createBackUri(array('module' => 'portfolio-write', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_Portfolio}'
                )
            ),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('title', 'detail', 'url'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'title' => array(
                    'text' => '{LNG_Topic}'
                ),
                'image' => array(
                    'text' => '',
                    'colspan' => 2
                ),
                'url' => array(
                    'text' => '{LNG_URL}'
                ),
                'create_date' => array(
                    'text' => '{LNG_Published date}',
                    'class' => 'center'
                ),
                'visited' => array(
                    'text' => '{LNG_Viewing}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'image' => array(
                    'class' => 'center'
                ),
                'published' => array(
                    'class' => 'center'
                ),
                'category_id' => array(
                    'class' => 'center'
                ),
                'create_date' => array(
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
                    'href' => $uri->createBackUri(array('module' => 'portfolio-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('portfolio_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        $item['url'] = $item['url'] == '' ? '' : '<a href="'.$item['url'].'" target=_blank>'.$item['url'].'</a>';
        if (is_file(ROOT_PATH.DATA_FOLDER.'portfolio/thumb_'.$item['id'].'.jpg')) {
            $item['image'] = '<img src="'.WEB_URL.DATA_FOLDER.'portfolio/thumb_'.$item['id'].'.jpg" title="'.$this->thumbnails[1].'" width=22 height=22 alt=thumbnail>';
        } else {
            $item['image'] = '<span class=icon-thumbnail title="'.$this->thumbnails[0].'"></span>';
        }
        $item['create_date'] = Date::format($item['create_date'], 'd M Y');
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
