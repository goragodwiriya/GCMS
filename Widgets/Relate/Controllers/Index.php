<?php
/**
 * @filesource Widgets/Relate/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Relate\Controllers;

use Gcms\Gcms;
use Kotchasan\Http\Request;
use Kotchasan\Template;

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
        if (!empty($query_string['module']) && !empty($query_string['id']) && $index = Gcms::$module->findByModule($query_string['module'])) {
            $cols = isset($query_string['cols']) ? (int) $query_string['cols'] : 1;
            $rows = isset($query_string['rows']) ? (int) $query_string['rows'] : 1;
            if ($cols > 0 && $rows > 0) {
                $style = isset($query_string['style']) && in_array($query_string['style'], array('list', 'icon', 'thumb')) ? $query_string['style'] : 'list';
                // /document/relate.html
                $template = Template::create('document', $index->module, 'relate');
                $template->add(array(
                    '/{DETAIL}/' => '<script>getWidgetNews("{ID}", "Relate", 0)</script>',
                    '/{ID}/' => (int) $query_string['id'].'_'.$cols.'_'.$rows.'_0_'.$style,
                    '/{MODULE}/' => $index->module,
                    '/{STYLE}/' => $style.'view',
                    '/{COLS}/' => $cols
                ));
                // คืนค่า HTML
                return $template->render();
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
        // id_cols_rows_sort_style
        if ($request->isReferer() && preg_match('/^([0-9]+)_([0-9]+)_([0-9]+)_([0-9]+)_([a-z]{0,})$/', $request->get('id')->toString(), $match)) {
            // query
            $index = \Widgets\Relate\Models\Index::get((int) $match[1], (int) $match[3], (int) $match[2]);
            if ($index) {
                echo \Widgets\Relate\Views\Index::render($index);
            }
        }
    }
}
