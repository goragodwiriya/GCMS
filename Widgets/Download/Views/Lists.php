<?php
/**
 * @filesource Widgets/Download/Views/Lists.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Download\Views;

use Kotchasan\Grid;
use Kotchasan\Text;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Lists extends \Gcms\View
{
    /**
     * List รายการไฟล์ดาวน์โหลด
     *
     * @param array $query_string ข้อมูลที่ส่งมาจากการเรียก Widget
     *
     * @return string
     */
    public static function render($index, $query_string)
    {
        $id = uniqid();
        // รายการ
        $listitem = Grid::create('download', $index->module, 'widgetitem');
        // query ข้อมูล
        foreach (\Widgets\Download\Models\Lists::get($index->module_id, $query_string['cat'], $query_string['count']) as $item) {
            $listitem->add(array(
                '/{ID}/' => $item->id,
                '/{NAME}/' => $item->name,
                '/{EXT}/' => $item->ext,
                '/{ICON}/' => WEB_URL.'skin/ext/'.(is_file(ROOT_PATH.'skin/ext/'.$item->ext.'.png') ? $item->ext : 'file').'.png',
                '/{DETAIL}/' => $item->detail,
                '/{DATE}/' => $item->last_update,
                '/{DOWNLOADS}/' => number_format($item->downloads),
                '/{SIZE}/' => Text::formatFileSize($item->size)
            ));
        }
        $content = '<div id="'.$id.'" class="document-list download listview">';
        $content .= \Gcms\View::create()->renderHTML($listitem->render());
        $content .= '</div>';
        $content .= '<script>initDownloadList("'.$id.'");</script>';
        // คืนค่า HTML
        return $content;
    }
}
