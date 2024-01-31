<?php
/**
 * @filesource Widgets/Rss/Controllers/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Rss\Controllers;

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
        $widget = array();
        if (!empty($query_string['module']) && preg_match('/([0-9]+)(_([0-9]+))?/', $query_string['module'], $match)) {
            $id = $match[1] == 0 ? '' : $match[1];
            $interval = empty($match[3]) ? 30 : $match[3];
        } else {
            $id = '';
            $interval = 30;
        }
        if (!empty(self::$cfg->rss_tabs)) {
            $tab = uniqid();
            $widget[] = '<div class="rss_widget">';
            $widget[] = '<div id=rss_tab_'.$tab.' class=rss_tab></div>';
            $widget[] = '<div id=rss_div_'.$tab.' class=rss_div></div>';
            $widget[] = '</div>';
            $widget[] = '<script>';
            $widget[] = "var rss = new GRSSTab('rss_tab_$tab','rss_div_$tab', $interval);";
            foreach (self::$cfg->rss_tabs as $item) {
                if ($item['index'] == $id) {
                    $widget[] = "rss.add('$item[url]', '$item[topic]', {rows:$item[rows],cols:$item[cols]});";
                }
            }
            $widget[] = 'rss.show(0);';
            $widget[] = '</script>';
        }
        // คืนค่า HTML
        return implode('', $widget);
    }
}
