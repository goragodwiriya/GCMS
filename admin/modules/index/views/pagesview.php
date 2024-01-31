<?php
/**
 * @filesource modules/index/views/pagesview.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Pagesview;

/**
 * ฟอร์ม forgot
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * @param  $date
     *
     * @return mixed
     */
    public function render($date)
    {
        $total = 0;
        $thead = array();
        $tbody = array();
        $list = \Index\Pagesview\Model::get($date);
        $first_dsate = strtotime($date.'-01');
        $end = date('t', $first_dsate);
        $month = date('Y-m-', $first_dsate);
        for ($i = 1; $i <= $end; $i++) {
            $dx = $month.sprintf('%02d', $i);
            if (isset($list[$dx])) {
                $d = '<a href="index.php?module=report&amp;date='.$dx.'">'.$i.'</a>';
                $total = $total + $list[$dx]['pages_view'];
            } else {
                $d = $i;
            }
            $thead[] = '<th>'.$d.'</th>';
            $tbody[] = '<td>'.(empty($list[$dx]['pages_view']) ? 0 : number_format($list[$dx]['pages_view'])).'</td>';
        }
        $content = '<section class="section margin-top">';
        $content .= '<div id=pageview_graph>';
        $content .= '<div class=datatable><div class=tablebody>';
        $content .= '<table class="data fullwidth border">';
        $content .= '<thead><tr><th class=nowrap>{LNG_date}</th>'.implode('', $thead).'</tr></thead>';
        $content .= '<tbody><tr><th class=nowrap>{LNG_Pages view}</th>'.implode('', $tbody).'</tr></tbody>';
        $content .= '</table>';
        $content .= '</div></div></div>';
        $content .= '</section>';
        $content .= '<script>new GGraphs("pageview_graph", {type: "spline", showTitle: false});</script>';
        return $content;
    }
}
