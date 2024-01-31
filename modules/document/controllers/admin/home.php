<?php
/**
 * @filesource modules/document/controllers/admin/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Admin\Home;

use Gcms\Gcms;
use Kotchasan\Http\Request;

/**
 * ข้อมูลหน้า Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * popular page
     *
     * @param Request    $request
     * @param Collection $grid
     * @param array      $login
     */
    public static function addGrid(Request $request, $grid, $login)
    {
        if (Gcms::$module->findInstalledOwners('document')) {
            $thead = array();
            $visited = array();
            foreach (\Document\Admin\Home\Model::popularpage() as $item) {
                $thead[] = '<th><a href="'.WEB_URL.'index.php?module='.$item['module'].'&amp;id='.$item['id'].'" target=_blank>'.$item['topic'].'</a></th>';
                $visited[] = '<td>'.$item['visited_today'].'</td>';
            }
            $content = '<section class=section>';
            $content .= '<header><h3 class=icon-pie>{LNG_Popular daily} ({LNG_Module} Document)</h3></header>';
            $content .= '<div id=visited_graph>';
            $content .= '<table class=hidden>';
            $content .= '<thead><tr><th>&nbsp;</th>'.implode('', $thead).'</tr></thead>';
            $content .= '<tbody>';
            $content .= '<tr><th>{LNG_Visited}</th>'.implode('', $visited).'</tr>';
            $content .= '</tbody>';
            $content .= '</table>';
            $content .= '</div>';
            $content .= '</section>';
            $content .= '<script>';
            $content .= 'new GGraphs("visited_graph", {type:"donut",colors:COLORS,strokeColor:null});';
            $content .= '</script>';
            \Index\Home\Controller::renderGrid($grid, $content, 6, 'large12');
        }
    }
}
