<?php
/**
 * @filesource Widgets/Download/Views/Download.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Download\Views;

use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Download extends \Gcms\View
{
    /**
     * ปุ่มดาวน์โหลด
     *
     * @param array $file
     *
     * @return string
     */
    public static function render($file)
    {
        // แสดงผลปุ่มดาวน์โหลด
        $icon = WEB_URL.'skin/ext/'.(is_file(ROOT_PATH.'skin/ext/'.$file['ext'].'.png') ? $file['ext'] : 'file').'.png';
        $content = '<a class="widget-download" id="download_'.$file['id'].'" title="';
        $content .= Language::get('Download').' '.$file['name'].'.'.$file['ext'].' '.Language::get('File size').' '.Text::formatFileSize($file['size']).' ('.Date::format($file['last_update']).')';
        $content .= '">'.$file['name'].'.'.$file['ext'].'&nbsp;<img class="nozoom" src="'.$icon.'" alt="'.$file['ext'].'">';
        $content .= '&nbsp;(<span id=downloads_'.$file['id'].'>'.number_format($file['downloads']).'</span>)</a>';
        if (MAIN_INIT === 'indexhtml') {
            $content .= '<script>initDownloadList("download_'.$file['id'].'");</script>';
        }
        // คืนค่า HTML
        return $content;
    }
}
