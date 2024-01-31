<?php
/**
 * @filesource modules/index/views/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Home;

use Kotchasan\Date;

/**
 * module=home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\KBase
{
    /**
     * กราฟ Page Views
     *
     * @return string
     */
    public static function pageViews()
    {
        $thead = array();
        $pageview = array();
        $visited = array();
        foreach (\Index\Home\Model::pageviews() as $i => $item) {
            $thead[] = '<th><a href="'.WEB_URL.'admin/index.php?module=pagesview&amp;date='.$item['year'].'-'.$item['month'].'">'.Date::monthName($item['month']).'</a></th>';
            $pageview[] = '<td>'.number_format($item['pages_view']).'</td>';
            $visited[] = '<td>'.number_format($item['visited']).'</td>';
        }
        $content = '<section class=section>';
        $content .= '<header><h3 class=icon-stats>{LNG_People visit the site}</h3></header>';
        $content .= '<div id=pageview_graph>';
        $content .= '<div class=datatable><div class=tablebody>';
        $content .= '<table class="data fullwidth border">';
        $content .= '<thead><tr><th>{LNG_monthly}</th>'.implode('', $thead).'</tr></thead>';
        $content .= '<tbody>';
        $content .= '<tr><th scope=row>{LNG_Total visitors}</th>'.implode('', $visited).'</tr>';
        $content .= '<tr class=bg2><th scope=row>{LNG_Pages view}</th>'.implode('', $pageview).'</tr>';
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</div></div>';
        $content .= '</div>';
        $content .= '</section>';
        $content .= '<script>';
        $content .= 'new GGraphs("pageview_graph", {type: "vchart", height: "239px"});';
        $content .= '</script>';
        return $content;
    }

    /**
     * ข่าวสารจาก GCMS
     *
     * @return string
     */
    public static function gcmsNews()
    {
        $content = '<section class=section>';
        $content .= '<header><h3 class=icon-rss>{LNG_News}</h3></header>';
        $content .= '<ol id=news_div></ol>';
        $content .= '<div class="bottom right padding-top-right">';
        $content .= '<a class=icon-next href="https://gcms.in.th/news.html" target=_blank>{LNG_all items}</a>';
        $content .= '</div>';
        $content .= '</section>';
        $content .= '<script>';
        $content .= "getNews('news_div');";
        $content .= '</script>';
        return $content;
    }
}
