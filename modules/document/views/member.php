<?php
/**
 * @filesource modules/document/views/member.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Member;

use Gcms\Gcms;
use Gcms\Login;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Http\Uri;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * แสดงเรื่องที่เขียนโดยสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ข้อมูลโมดูล
     */
    private $index;
    /**
     * @var mixed
     */
    private $categories;

    /**
     * แสดงเรื่องที่เขียนโดยสมาชิก
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function render(Request $request, $index)
    {
        if ($login = Login::isMember()) {
            // ข้อมูลโมดูล
            $this->index = Gcms::$module->findByModule($request->request('tab')->filter('a-z0-9'));
            if ($this->index) {
                // หมวดหมู่
                $category_id = $request->request('cat', 0)->toInt();
                // Uri
                $uri = Uri::createFromUri(WEB_URL.'index.php?module=editprofile&tab='.$this->index->module.($category_id > 0 ? '&cat='.$category_id : ''));
                // หมวดหมู่
                $this->categories = \Index\Category\Model::categories((int) $this->index->module_id);
                // URL สำหรับส่งให้ตาราง
                $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
                // ตาราง
                $table = new DataTable(array(
                    /* Uri */
                    'uri' => $uri,
                    /* className */
                    'class' => 'horiz-table border',
                    /* Model */
                    'model' => 'Document\Admin\Setup\Model',
                    /* query where */
                    'defaultFilters' => array(
                        array('module_id', (int) $this->index->module_id),
                        array('member_id', (int) $login['id']),
                        array('index', 0),
                        array('language', array(Language::name(), ''))
                    ),
                    /* เรียงลำดับ */
                    'sort' => 'P.id desc',
                    /* Uri */
                    'uri' => $uri,
                    /* รายการต่อหน้า */
                    'perPage' => $request->cookie('document_perPage', 30)->toInt(),
                    /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
                    'onRow' => array($this, 'onRow'),
                    'actions' => array(
                        array(
                            'class' => 'button green icon-plus',
                            'href' => $uri->withParams(array('tab' => 'documentwrite', 'mid' => $this->index->module_id, 'cat' => $category_id)),
                            'text' => '{LNG_Add New} {LNG_Content}'
                        )
                    ),
                    /* คอลัมน์ที่ไม่ต้องแสดงผล */
                    'hideColumns' => array('member_id', 'id', 'status', 'module_id', 'show_news', 'can_reply', 'writer', 'index', 'language', 'detail'),
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
                            'class' => 'left'
                        ),
                        'picture' => array(
                            'text' => '',
                            'colspan' => 2
                        ),
                        'module_id' => array(
                            'text' => '{LNG_Module}',
                            'class' => 'center'
                        ),
                        'category_id' => array(
                            'text' => '{LNG_Category}'
                        ),
                        'create_date' => array(
                            'text' => '{LNG_Article Date}'
                        ),
                        'last_update' => array(
                            'text' => '{LNG_Last updated}'
                        ),
                        'visited' => array(
                            'text' => '{LNG_Viewing}'
                        )
                    ),
                    /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
                    'cols' => array(
                        'topic' => array(
                            'class' => 'left'
                        ),
                        'picture' => array(
                            'class' => 'center'
                        ),
                        'published' => array(
                            'class' => 'center'
                        ),
                        'module_id' => array(
                            'class' => 'center'
                        ),
                        'category_id' => array(
                            'class' => 'center'
                        ),
                        'create_date' => array(
                            'class' => 'date'
                        ),
                        'last_update' => array(
                            'class' => 'date'
                        ),
                        'visited' => array(
                            'class' => 'visited'
                        )
                    ),
                    /* ปุ่มแสดงในแต่ละแถว */
                    'buttons' => array(
                        'edit' => array(
                            'class' => 'icon-edit button green',
                            'href' => $uri->withParams(array('tab' => 'documentwrite', 'id' => ':id', 'mid' => ':module_id')),
                            'text' => '{LNG_Edit}'
                        )
                    )
                ));
                // save cookie
                setcookie('document_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
                // ตาราง
                $detail = $table->render();
            } else {
                // ไม่เผยแพร่
                $detail = '<div class=error>{LNG_Can not be performed this request. Because they do not find the information you need or you are not allowed}</div>';
            }
            // /document/member.html
            $template = Template::create('document', $this->index->module, 'member');
            $template->add(array(
                '/{LIST}/' => $detail,
                '/{TITLE}/' => ucfirst($this->index->module)
            ));
            $index->detail = $template->render();
            // คืนค่า
            return $index;
        }
        return null;
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
        $item['topic'] = '<a href="'.WEB_URL.'index.php?module='.$this->index->module.'&amp;id='.$item['id'].'" target=_blank>'.$item['topic'].'</a>';
        if (is_file(ROOT_PATH.DATA_FOLDER.'document/'.$item['picture'])) {
            $item['picture'] = '<img src="'.WEB_URL.DATA_FOLDER.'document/'.$item['picture'].'" width=22 height=22 alt=thumbnail>';
        } else {
            $item['picture'] = '';
        }
        $item['create_date'] = Date::format($item['create_date'], 'd M Y H:i');
        $item['category_id'] = empty($item['category_id']) || empty($this->categories[$item['category_id']]) ? '-' : $this->categories[$item['category_id']];
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i');
        $item['published'] = '<span class="icon-published'.$item['published'].'"></span>';
        return $item;
    }
}
