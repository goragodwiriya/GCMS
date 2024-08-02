<?php
/**
 * @filesource Widgets/Album/Views/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Album\Views;

use Gallery\Index\Controller;
use Gcms\Gcms;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\View
{
    /**
     * แสดงผล Widget
     *
     * @param array $query_string ข้อมูลที่ส่งมาจากการเรียก Widget
     *
     * @return string
     */
    public static function render($query_string)
    {
        $dir = DATA_FOLDER.'gallery/';
        $widget = '';
        foreach (\Widgets\Album\Models\Index::get($query_string) as $item) {
            $img = is_file(ROOT_PATH.$dir.$item->id.'/'.$item->image) ? WEB_URL.$dir.$item->id.'/thumb_'.$item->image : WEB_URL.'modules/gallery/img/noimage.jpg';
            $url = Controller::url($item->module, $item->id);
            $widget .= '<div class=item>';
            $widget .= '<a href="'.$url.'" title="'.$item->topic.'">';
            $widget .= '<figure style="background-image:url('.$img.')"></figure>';
            $widget .= '<figcaption>'.$item->topic.'</figcaption>';
            $widget .= '</a></div>';
        }
        $content = '';
        if (!empty($query_string['title'])) {
            // ตรวจสอบโมดูล
            $index = Gcms::$module->findByOwner('gallery');
            if (!empty($index)) {
                $content .= '<h5><span>'.$index[0]->topic.'</span></h5>';
            }
        }
        if ($widget != '') {
            $content .= '<div class="widget-album document-list col'.$query_string['cols'].' small2 medium4 large4 thumbview">'.$widget.'</div>';
        }
        return '<article>'.$content.'</article>';
    }
}
