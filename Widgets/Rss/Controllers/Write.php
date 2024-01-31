<?php
/**
 * @filesource Widgets/Rss/Controllers/Write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Rss\Controllers;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * เขียน-แก้ไข
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Write extends \Gcms\Controller
{
    /**
     * แสดงผล
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        if (defined('MAIN_INIT')) {
            // ข้อความ title bar
            $this->title = Language::trans('{LNG_Create or Edit} {LNG_RSS Tab}');
            // เมนู
            $this->menu = 'widgets';
            // สามารถตั้งค่าระบบได้
            if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
                // รายการที่ต้องการ
                $id = $request->request('id')->toInt();
                if ($id == 0) {
                    $datas = array(
                        'url' => '',
                        'topic' => '',
                        'index' => '',
                        'rows' => 3,
                        'cols' => 2,
                        'id' => 0
                    );
                } elseif (isset(self::$cfg->rss_tabs[$id])) {
                    $datas = self::$cfg->rss_tabs[$id];
                    $datas['id'] = $id;
                } else {
                    $datas = null;
                }
                if ($datas) {
                    // แสดงผล
                    $section = Html::create('section');
                    // breadcrumbs
                    $breadcrumbs = $section->add('div', array(
                        'class' => 'breadcrumbs'
                    ));
                    $ul = $breadcrumbs->add('ul');
                    $ul->appendChild('<li><span class="icon-widgets">{LNG_Widgets}</span></li>');
                    $ul->appendChild('<li><span>{LNG_RSS Tab}</span></li>');
                    $ul->appendChild('<li><span>{LNG_'.($id == 0 ? 'Create' : 'Edit').'}</span></li>');
                    $section->add('header', array(
                        'innerHTML' => '<h2 class="icon-rss">'.$this->title().'</h2>'
                    ));
                    // แสดงฟอร์ม
                    $section->appendChild(\Widgets\Rss\Views\Write::create()->render($datas));
                    // คืนค่า HTML
                    return $section->render();
                }
            }
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
