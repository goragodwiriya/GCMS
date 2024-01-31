<?php
/**
 * @filesource Widgets/Document/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Document\Controllers;

use Gcms\Gcms;
use Kotchasan\Grid;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * แสดงผล Widget
     *
     * @param array $query_string ข้อมูลที่ส่งมาจากการเรียก Widget
     *
     * @return string
     */
    public function get($query_string)
    {
        if (!empty(Gcms::$module) && !empty($query_string['module']) && $index = Gcms::$module->findByModule($query_string['module'])) {
            if ($index->owner == 'document') {
                // ค่าที่ส่งมา
                $cols = isset($query_string['cols']) ? (int) $query_string['cols'] : 1;
                if (isset($query_string['count'])) {
                    $rows = ceil($query_string['count'] / $cols);
                } elseif (isset($query_string['rows'])) {
                    $rows = (int) $query_string['rows'];
                }
                if (empty($rows)) {
                    $rows = ceil((int) $index->news_count / $cols);
                }
                if ($rows > 0 && $cols > 0) {
                    $cat = isset($query_string['cat']) ? $query_string['cat'] : 0;
                    $sort = isset($query_string['sort']) ? (int) $query_string['sort'] : $index->news_sort;
                    $show = isset($query_string['show']) && preg_match('/^[a-z0-9]+$/', $query_string['show']) ? $query_string['show'] : '';
                    $style = isset($query_string['style']) && in_array($query_string['style'], array('listview', 'iconview', 'thumbview')) ? $query_string['style'] : 'listview';
                    $title = isset($query_string['title']) ? $query_string['title'] : 0;
                    // widgetitem.html
                    $listitem = Grid::create('document', $index->module, 'widgetitem');
                    // เครื่องหมาย new
                    $valid_date = time() - (int) $index->new_date;
                    // query ข้อมูล
                    $items = \Widgets\Document\Models\Index::get($index->module_id, $cat, $show, $sort, $rows * $cols);
                    foreach ($items as $i => $item) {
                        $listitem->add(\Widgets\Document\Views\Index::renderItem($index, $item, $valid_date, $cols));
                    }
                    // คืนค่า HTML
                    $content = '<div class="document-list '.$index->module.' col'.$cols.' '.$style.'">'.(empty($items) ? '' : $listitem->render()).'</div>';
                    if (!empty($title)) {
                        if (empty($cat)) {
                            $topic = $index->topic;
                        } else {
                            $category = \Index\Category\Model::get($cat, $index->module_id);
                            $topic = $category->topic;
                        }
                        $content = '<article><h5><span>'.$topic.'</span></h5>'.$content.'</article>';
                    }
                    return $content;
                }
            }
        }
        return '';
    }
}
