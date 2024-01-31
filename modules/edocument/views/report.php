<?php
/**
 * @filesource modules/edocument/views/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Edocument\Report;

use Gcms\Login;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Http\Uri;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * module=editprofile&tab=edocumentreport
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * รายงานการดาวน์โหลด
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public function render(Request $request, $index)
    {
        if (Login::isMember()) {
            // ตรวจสอบรายการที่ต้องการ
            $index = \Edocument\Write\Model::get($request->request('id')->toInt(), $index);
            // ตาราง
            $table = new DataTable(array(
                /* Model */
                'model' => 'Edocument\Admin\Report\Model',
                /* เรียงลำดับ */
                'sort' => 'last_update DESC',
                /* คอลัมน์ที่ไม่ต้องแสดงผล */
                'hideColumns' => array('email', 'id', 'document_id'),
                /* Uri */
                'uri' => Uri::createFromUri(WEB_URL.'index.php?module=editprofile&tab='.$index->tab.'&id='.$index->id),
                /* รายการต่อหน้า */
                'perPage' => $request->cookie('edocument_perPage', 30)->toInt(),
                /* query where */
                'defaultFilters' => array(
                    array('document_id', $index->id)
                ),
                /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
                'onRow' => array($this, 'onRow'),
                /* คอลัมน์ที่สามารถค้นหาได้ */
                'searchColumns' => array('name', 'email'),
                /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
                'headers' => array(
                    'name' => array(
                        'text' => '{LNG_Name} {LNG_Surname}'
                    ),
                    'status' => array(
                        'text' => '{LNG_Recipient}',
                        'class' => 'center'
                    ),
                    'last_update' => array(
                        'text' => '{LNG_Lastest}',
                        'class' => 'center'
                    ),
                    'downloads' => array(
                        'text' => '{LNG_Download}',
                        'class' => 'center'
                    )
                ),
                /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
                'cols' => array(
                    'status' => array(
                        'class' => 'center'
                    ),
                    'downloads' => array(
                        'class' => 'center reply'
                    ),
                    'last_update' => array(
                        'class' => 'date'
                    )
                )
            ));
            // save cookie
            setcookie('edocument_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
            // /edocument/report.html
            $template = Template::create('edocument', 'edocument', 'report');
            $template->add(array(
                '/{TOPIC}/' => $index->module->topic,
                '/{NO}/' => $index->document_no,
                '/{DETAIL}/' => $index->detail,
                '/{LIST}/' => $table->render()
            ));
            // คืนค่า
            $index->topic = Language::get('Download Details').' '.$index->document_no;
            $index->detail = $template->render();
            // คืนค่า HTML
            return $index;
        }
        // not member
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
        $item['name'] = '<a href="'.WEB_URL.'index.php?module=member&amp;id='.$item['id'].'">'.(empty($item['name']) ? $item['email'] : $item['name']).'</a>';
        $item['last_update'] = Date::format($item['last_update'], 'd M Y H:i:s');
        $item['status'] = isset(self::$cfg->member_status[$item['status']]) ? '<span class=status'.$item['status'].'>'.self::$cfg->member_status[$item['status']].'</span>' : '';
        return $item;
    }
}
