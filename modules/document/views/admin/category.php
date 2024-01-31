<?php
/**
 * @filesource modules/document/views/admin/category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Category;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=document-category
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
    private $replies;

    /**
     * แสดงรายการหมวดหมู่
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        $this->replies = Language::get('REPLIES');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* ข้อมูลใส่ลงในตาราง */
            'datas' => \Index\Admincategory\Model::toDataTable((int) $index->module_id),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('module_id', 'id', 'group_id', 'c2'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/model/admincategory/action?mid='.$index->module_id,
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
                    'href' => $uri->createBackUri(array('module' => 'document-categorywrite', 'mid' => $index->module_id)),
                    'text' => '{LNG_Add New} {LNG_Category}'
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'category_id' => array(
                    'text' => '{LNG_ID}'
                ),
                'published' => array(
                    'text' => '{LNG_Status}',
                    'class' => 'center'
                ),
                'topic' => array(
                    'text' => '{LNG_Category}'
                ),
                'detail' => array(
                    'text' => '{LNG_Description}'
                ),
                'config' => array(
                    'text' => '{LNG_Settings}',
                    'class' => 'center'
                ),
                'c1' => array(
                    'text' => '{LNG_Contents}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'published' => array(
                    'class' => 'center'
                ),
                'config' => array(
                    'class' => 'center'
                ),
                'c1' => array(
                    'class' => 'visited center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'document-categorywrite', 'mid' => $index->module_id, 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
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
        $item['topic'] = $this->unserialize($item['topic']);
        $item['detail'] = $this->unserialize($item['detail']);
        $item['category_id'] = '<label><input type=text class=number size=5 id=categoryid_'.$item['id'].' value="'.$item['category_id'].'" title="{LNG_Edit}"></label>';
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        $config = @unserialize($item['config']);
        if (is_array($config)) {
            $icons = array(
                '<span class="icon-reply reply'.$config['can_reply'].'" title="'.$this->replies[$config['can_reply']].'"></span>'
            );
            if (isset($config['published'])) {
                $icons[] = '<span class="icon-published'.$config['published'].'" title="'.$this->publisheds[$config['published']].'"></span>';
            }
            $item['config'] = '<span class=nowrap>'.implode(' ', $icons).'</span>';
        } else {
            $item['config'] = '';
        }
        return $item;
    }

    /**
     * เตรียมข้อมูล topic, detail สำหรับใส่ลงในตาราง
     *
     * @param string $item
     *
     * @return string
     */
    private function unserialize($item)
    {
        $datas = array();
        foreach (unserialize($item) as $lng => $value) {
            $datas[$lng] = empty($lng) ? $value : '<p style="background:0 50% url('.WEB_URL.'language/'.$lng.'.gif) no-repeat;padding-left:21px;">'.$value.'</p>';
        }
        return implode('', $datas);
    }
}
