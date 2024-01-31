<?php
/**
 * @filesource modules/board/views/admin/category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Admin\Category;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=board-category
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
    private $img_law;

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
        $this->img_law = Language::get('IMG_LAW');
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
            'hideColumns' => array('module_id', 'id', 'group_id'),
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
                    'href' => $uri->createBackUri(array('module' => 'board-categorywrite', 'mid' => $index->module_id)),
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
                'config' => array(
                    'text' => '{LNG_Settings}',
                    'class' => 'center'
                ),
                'detail' => array(
                    'text' => '{LNG_Description}'
                ),
                'c1' => array(
                    'text' => '{LNG_Post}',
                    'class' => 'center'
                ),
                'c2' => array(
                    'text' => '{LNG_comments}',
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
                ),
                'c2' => array(
                    'class' => 'comment center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'board-categorywrite', 'mid' => $index->module_id, 'id' => ':id')),
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
                empty($config['can_post']) || !is_array($config['can_post']) ? '' : '<span class="icon-newtopic" title="{LNG_Posting} '.$this->cfgToStr($config['can_post']).'"></span>',
                empty($config['can_reply']) || !is_array($config['can_reply']) ? '' : '<span class="icon-chat reply1" title="{LNG_Comment} '.$this->cfgToStr($config['can_reply']).'"></span>',
                empty($config['can_view']) || !is_array($config['can_view']) ? '' : '<span class="icon-visited color-red" title="{LNG_Viewing} '.$this->cfgToStr($config['can_view']).'"></span>',
                empty($config['moderator']) || !is_array($config['moderator']) ? '' : '<span class="icon-customer color-blue" title="{LNG_Moderator} '.$this->cfgToStr($config['moderator']).'"></span>',
                empty($config['img_upload_type']) ? '' : '{LNG_Type} <b>'.implode(', ', $config['img_upload_type']).'</b> {LNG_File size} <b>'.$config['img_upload_size'].' Kb.</b> '.(isset($this->img_law[$config['img_law']]) ? $this->img_law[$config['img_law']] : '')
            );
            $item['config'] = '<span class=nowrap>'.implode(' ', $icons).'</span>';
        } else {
            $item['config'] = '';
        }
        return $item;
    }

    /**
     * @param $cfg
     */
    private function cfgToStr($cfg)
    {
        $ret = array();
        foreach ($cfg as $item) {
            if ($item == -1) {
                $ret[] = '{LNG_Guest}';
            } else {
                $ret[] = self::$cfg->member_status[$item];
            }
        }
        return implode(', ', $ret);
    }

    /**
     * เตรียมข้อมูล topic, detail สำหรับใส่ลงในตาราง
     *
     * @param array $item
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
