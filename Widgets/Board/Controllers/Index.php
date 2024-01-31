<?php
/**
 * @filesource Widgets/Board/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Board\Controllers;

use Gcms\Gcms;
use Kotchasan\Grid;
use Kotchasan\Http\Request;
use Kotchasan\Template;
use Widgets\Board\Views\Index as View;

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
        if (!empty($query_string['module']) && $index = Gcms::$module->findByModule($query_string['module'])) {
            if ($index->owner == 'board') {
                // ค่าที่ส่งมา
                if (isset($query_string['cat']) && preg_match('/^([0-9,]+)$/', $query_string['cat'], $match)) {
                    $cat = $match[1];
                } else {
                    $cat = 0;
                }
                $interval = isset($query_string['interval']) ? (int) $query_string['interval'] : 0;
                $count = isset($query_string['count']) ? (int) $query_string['count'] : $index->news_count;
                if ($count > 0) {
                    // widget.html
                    $template = Template::create('board', $index->module, 'widget');
                    $template->add(array(
                        '/{DETAIL}/' => '<script>getWidgetNews("{ID}", "Board", '.$interval.')</script>',
                        '/{ID}/' => $index->module_id.'_'.$cat.'_'.$count,
                        '/{MODULE}/' => $index->module
                    ));
                    return $template->render();
                }
            }
        }
    }

    /**
     * อ่านข้อมูลจาก Ajax
     *
     * @param Request $request
     *
     * @return string
     */
    public function getWidgetNews(Request $request)
    {
        if ($request->isReferer() && preg_match('/^([0-9]+)_([0-9,]+)_([0-9]+)$/', $request->post('id')->toString(), $match)) {
            // ตรวจสอบโมดูล
            $index = \Index\Module\Model::getModuleWithConfig('board', '', $match[1]);
            if ($index) {
                // widgetitem.html
                $listitem = Grid::create('board', $index->module, 'widgetitem');
                // เครื่องหมาย new
                $valid_date = time() - (int) $index->new_date;
                // query ข้อมูล
                $items = \Widgets\Board\Models\Index::get($match[1], $match[2], $match[3]);
                foreach ($items as $item) {
                    $listitem->add(View::renderItem($index, $item, $valid_date));
                }
                echo empty($items) ? '' : \Gcms\View::create()->renderHTML($listitem->render());
            }
        }
    }
}
