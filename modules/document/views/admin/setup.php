<?php
/**
 * @filesource modules/document/views/admin/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Setup;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=document-setup
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
     * @var array
     */
    private $replies;
    /**
     * @var array
     */
    private $thumbnails;
    /**
     * @var string
     */
    private $default_icon;
    /**
     * @var object
     */
    private $categories;

    /**
     * ตาราง บทความ
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
        $this->replies = Language::get('REPLIES');
        $this->thumbnails = Language::get('THUMBNAILS');
        $this->default_icon = WEB_URL.$index->default_icon;
        $this->categories = \Index\Category\Model::categories((int) $index->module_id);
        $category_id = $request->request('cat')->toInt();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'admin/index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => 'Document\Admin\Setup\Model',
            /* เรียงลำดับ */
            'sort' => 'id DESC',
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('document_perPage', 30)->toInt(),
            /* query where */
            'defaultFilters' => array(
                array('module_id', (int) $index->module_id),
                array('index', 0),
                array('language', array(Language::name(), ''))
            ),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('member_id', 'id', 'status', 'module_id', 'index', 'language', 'detail'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/document/model/admin/setup/action?mid='.$index->module_id,
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
                    'href' => $uri->createBackUri(array('module' => 'document-write', 'mid' => $index->module_id, 'cat' => $category_id)),
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
                    'text' => '{LNG_Topic}',
                    'sort' => 'topic'
                ),
                'picture' => array(
                    'text' => '',
                    'colspan' => 4
                ),
                'category_id' => array(
                    'text' => '{LNG_Category}',
                    'class' => 'center'
                ),
                'writer' => array(
                    'text' => '{LNG_Writer}'
                ),
                'create_date' => array(
                    'text' => '{LNG_Article Date}',
                    'class' => 'center',
                    'sort' => 'create_date'
                ),
                'last_update' => array(
                    'text' => '{LNG_Last updated}',
                    'class' => 'center',
                    'sort' => 'last_update'
                ),
                'visited' => array(
                    'text' => '{LNG_Viewing}',
                    'class' => 'center',
                    'sort' => 'visited'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'picture' => array(
                    'class' => 'center'
                ),
                'can_reply' => array(
                    'class' => 'center'
                ),
                'published' => array(
                    'class' => 'center'
                ),
                'show_news' => array(
                    'class' => 'center'
                ),
                'category_id' => array(
                    'class' => 'center'
                ),
                'create_date' => array(
                    'class' => 'center date'
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
                    'href' => $uri->createBackUri(array('module' => 'document-write', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('document_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
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
        if (is_file(ROOT_PATH.DATA_FOLDER.'document/'.$item['picture'])) {
            $item['picture'] = '<img src="'.WEB_URL.DATA_FOLDER.'document/'.$item['picture'].'" title="'.$this->thumbnails[1].'" width=22 height=22 alt=thumbnail>';
        } else {
            $item['picture'] = '<span class=icon-thumbnail title="'.$this->thumbnails[0].'"></span>';
        }
        $item['show_news'] = '<span class="icon-widgets reply'.(preg_match('/news=1/', $item['show_news']) ? 1 : 0).'"></span>';
        $item['create_date'] = Date::format($item['create_date'], 'd M Y H:i');
        $item['category_id'] = empty($item['category_id']) || empty($this->categories[$item['category_id']]) ? '{LNG_Uncategorized}' : $this->categories[$item['category_id']];
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        $item['writer'] = '<span class="status'.$item['status'].'">'.$item['writer'].'</span>';
        $item['can_reply'] = '<a id=can_reply_'.$item['id'].' class="icon-reply reply'.$item['can_reply'].'" title="'.$this->replies[$item['can_reply']].'"></a>';
        $item['published'] = '<a id=published_'.$item['id'].' class="icon-published'.$item['published'].'" title="'.$this->publisheds[$item['published']].'"></a>';
        return $item;
    }
}
