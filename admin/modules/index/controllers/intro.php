<?php
/**
 * @filesource modules/index/controllers/intro.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Intro;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=intro
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มตั้งค่าหน้า intro
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Enable/Disable intro page');
        // เลือกเมนู
        $this->menu = 'settings';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // ภาษาที่ต้องการ
            $language = $request->request('language', Language::name())->toString();
            if (preg_match('/^[a-z]{2,2}$/', $language)) {
                // intro detail
                $template = ROOT_PATH.DATA_FOLDER.'intro.'.$language.'.php';
                if (is_file($template)) {
                    $template = trim(preg_replace('/<\?php exit([\(\);])?\?>/', '', file_get_contents($template)));
                } else {
                    $template = '<p style="padding: 20px; text-align: center; font-weight: bold;"><a href="index.php">Welcome<br>ยินดีต้อนรับ</a></p>';
                }
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('div', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-settings">{LNG_Site settings}</span></li>');
                $ul->appendChild('<li><span>{LNG_Intro page}</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
                ));
                // แสดงฟอร์ม
                $section->appendChild(\Index\Intro\View::create()->render($language, $template));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
