<?php
/**
 * @filesource widgets/personnel/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Personnel\Controllers;

use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget Personnel
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Display Widget
     *
     * @param array $query_string
     *
     * @return string
     */
    public function get($query_string)
    {
        $module = empty($query_string['module']) ? 'personnel' : $query_string['module'];
        $index = Gcms::$module->findByModule($module);
        if ($index) {
            $department = isset($query_string['cat']) ? $query_string['cat'] : '';
            $level = isset($query_string['level']) ? (int) $query_string['level'] : 1;
            $items = \Widgets\Personnel\Models\Index::getWidget($index->module_id, $department, $level);
            // layout=fade ซ้อนรูปทั้งหมดแล้วสลับแสดงทีละคนด้วย CSS (ต้องมีมากกว่า 1 คน)
            $fade = isset($query_string['layout']) && $query_string['layout'] === 'fade' && count($items) > 1;
            $widget = [];
            $widget[] = '<div class="widget-personnel'.($fade ? ' personnel-fade' : '').'">';
            $widget[] = '<div class="personnel-items"'.($fade ? ' style="--personnel-count:'.count($items).'"' : '').'>';
            foreach ($items as $i => $item) {
                if (file_exists(ROOT_PATH.DATA_FOLDER.'personnel/'.$item->picture)) {
                    $img = WEB_URL.DATA_FOLDER.'personnel/'.$item->picture;
                } else {
                    $img = WEB_URL.'images/no-image.webp';
                }
                $url = WEB_URL.'personnel?department='.$item->department;
                $widget[] = '<a class="widget-person-card level-'.$item->level.'" href="'.$url.'"'.($fade ? ' style="--personnel-index:'.$i.'"' : '').'>';
                $widget[] = '<img src="'.$img.'" alt="'.Text::htmlspecialchars($item->name).'" loading="lazy">';
                $widget[] = '<span class="widget-person-name">'.Text::htmlspecialchars($item->name).'</span>';
                $widget[] = '<span class="widget-person-position">'.Text::htmlspecialchars($item->position).'</span>';
                $widget[] = '</a>';
            }
            $widget[] = '</div>';

            if (!empty($query_string['menu'])) {
                $widget[] = '<nav class="sidemenu"><ul>';
                foreach (\Personnel\Category\Model::toOptions($index->module_id, 'department') as $department) {
                    $topic = Text::htmlspecialchars($department['text']);
                    $widget[] = '<li><a href="'.WEB_URL.'personnel?department='.$department['value'].'"><span>'.$topic.'</span></a></li>';
                }
                $widget[] = '</ul></nav>';
            }
            $widget[] = '</div>';
            // คืนค่า HTML
            return implode('', $widget);
        }

        return '';
    }
}
