<?php
/**
 * @filesource modules/index/controllers/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Home;

use Gcms\Login;
use Kotchasan\Collection;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Dashboard
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ไตเติล
        $this->title = Language::get('Dashboard');
        // เมนู
        $this->menu = 'home';
        // แอดมิน
        if ($login = Login::adminAccess()) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-home">{LNG_Home}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-dashboard">'.$this->title.'</h2>'
            ));
            // Card
            $card = new Collection();
            $counter = \Index\Home\Model::counter();
            self::renderCard($card, 'icon-visited', '{LNG_People online}', number_format(isset($counter['useronline']) ? $counter['useronline'] : 0));
            self::renderCard($card, 'icon-clock', '{LNG_Visitors today}', number_format(isset($counter['visited']) ? $counter['visited'] : 0), 'index.php?module=report');
            self::renderCard($card, 'icon-sex', '{LNG_Total visitors}', number_format(isset($counter['counter']) ? $counter['counter'] : 0));
            self::renderCard($card, 'icon-users', '{LNG_Total members}', number_format(isset($counter['members']) ? $counter['members'] : 0), 'index.php?module=member&amp;sort=id%20desc');
            // grid
            $grid = new Collection();
            if (!self::$cfg->production) {
                self::renderGrid($grid, \Index\Home\View::pageViews(), 8, 'large12');
                self::renderGrid($grid, \Index\Home\View::gcmsNews(), 4, 'large12');
            } else {
                self::renderGrid($grid, \Index\Home\View::pageViews(), 12);
            }
            // quick menu
            $menu = new Collection();
            // โหลด Component หน้า Home
            $dir = ROOT_PATH.'modules/';
            $f = @opendir($dir);
            if ($f) {
                while (false !== ($text = readdir($f))) {
                    if ($text != '.' && $text != '..' && $text != 'index' && $text != 'css' && $text != 'js' && is_dir($dir.$text)) {
                        if (is_file($dir.$text.'/controllers/admin/home.php')) {
                            require_once $dir.$text.'/controllers/admin/home.php';
                            $className = '\\'.ucfirst($text).'\Admin\Home\Controller';
                            if (method_exists($className, 'addCard')) {
                                $className::addCard($request, $card, $login);
                            }
                            if (method_exists($className, 'addMenu')) {
                                $className::addMenu($request, $menu, $login);
                            }
                            if (method_exists($className, 'addGrid')) {
                                $className::addGrid($request, $grid, $login);
                            }
                        }
                    }
                }
                closedir($f);
            }
            // dashboard
            $dashboard = $section->add('div', array(
                'class' => 'card-box clear'
            ));
            // render card
            foreach ($card as $item) {
                $dashboard->add('div', array(
                    'class' => 'card',
                    'innerHTML' => $item
                ));
            }
            // render quick menu
            if ($menu->count() > 0) {
                $dashboard->add('h3', array(
                    'innerHTML' => '<span class=icon-menus>{LNG_Quick Menu}</span>'
                ));
                $n = 0;
                foreach ($menu as $k => $item) {
                    if ($n == 0 || $n % 4 == 0) {
                        $ggrid = $dashboard->add('div', array(
                            'class' => 'ggrid row'
                        ));
                    }
                    $ggrid->add('section', array(
                        'class' => 'qmenu block3 float-left',
                        'innerHTML' => $item
                    ));
                    ++$n;
                }
            }
            // render Grid
            $c = $grid->count();
            if ($c > 0) {
                --$c;
                $size = 0;
                foreach ($grid as $k => $item) {
                    if ($c == $k && $size == 0) {
                        $ggrid->add('div', array(
                            'class' => 'block12',
                            'innerHTML' => $item['content']
                        ));
                    } else {
                        if ($size == 0) {
                            $ggrid = $section->add('div', array(
                                'class' => 'ggrid collapse dashboard'
                            ));
                        }
                        $size += $item['size'];
                        if ($size >= 12) {
                            $size = 0;
                        }
                        $ggrid->add('div', array(
                            'class' => 'block'.$item['size'].$item['class'],
                            'innerHTML' => $item['content']
                        ));
                    }
                }
            }
            if (!self::$cfg->production) {
                // GCMS Version
                $section->script("getUpdate('".self::$cfg->version."');");
            }
            // คืนค่า HTML
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }

    /**
     * ฟังก์ชั่นสร้าง card ในหน้า Home
     *
     * @param Collection $card
     * @param string     $icon
     * @param string     $title
     * @param string     $value
     * @param string     $url   default null
     */
    public static function renderCard($card, $icon, $title, $value, $url = null)
    {
        $content = '<a class="card-item"'.($url === null ? '' : ' href="'.$url.'"').'>';
        $content .= '<span class="card-subitem '.$icon.' icon"></span>';
        $content .= '<span class="card-subitem">';
        $content .= '<b class="cuttext">'.$value.'</b>';
        $content .= '<span class="cuttext title">'.$title.'</span>';
        $content .= '</span>';
        $content .= '</a>';
        $card->set(uniqid(), $content);
    }

    /**
     * ฟังก์ชั่นสร้าง เมนูด่วน ในหน้า Home
     *
     * @param Collection $menu
     * @param string     $icon
     * @param string     $title
     * @param string     $url
     */
    public static function renderQuickMenu($menu, $icon, $title, $url)
    {
        $menu->set($title, '<a href="'.$url.'"><span class="'.$icon.'">'.$title.'</span></a>');
    }

    /**
     * ฟังก์ชั่นสร้าง Grid ในหน้า Home
     *
     * @param Collection $grid
     * @param string     $content
     * @param int        $size    Grid Size 1 - 12
     * @param string     $class
     */
    public static function renderGrid($grid, $content, $size = 6, $class = '')
    {
        $grid->set($grid->count(), array(
            'size' => $size,
            'class' => ' '.$class,
            'content' => $content
        ));
    }
}
