<?php
/**
 * @filesource modules/index/controllers/template.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Template;

use Gcms\Config;
use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=template
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายการ template
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Select a template of the site');
        // เลือกเมนู
        $this->menu = 'settings';
        // สามารถตั้งค่าระบบได้
        if ($login = Login::checkPermission(Login::adminAccess(), 'can_config')) {
            // โหลด config
            $config = Config::load(CONFIG);
            // path ของ skin
            $dir = ROOT_PATH.'skin';
            // action
            $action = $request->request('action')->toString();
            if (!empty($action)) {
                if (Login::notDemoMode($login)) {
                    $theme = preg_replace('/[\/\\\\]/ui', '', $request->request('theme')->text());
                    if (is_dir($dir."/$theme")) {
                        if ($action == 'use') {
                            // skin ที่กำหนด
                            $config->skin = $theme;
                            unset($_SESSION['skin']);
                            // บันทึก config.php
                            if (Config::save($config, CONFIG)) {
                                $request->setSession('my_skin', $config->skin);
                                $message = '<aside class=message>{LNG_Select a new template successfully}</aside>';
                            } else {
                                $message = '<aside class=error>'.Language::replace('File %s cannot be created or is read-only.', 'settings/config.php').'</aside>';
                            }
                        } elseif ($action == 'delete') {
                            // ลบ skin
                            File::removeDirectory($dir.'/'.$theme.'/');
                            $message = '<aside class=message>{LNG_Successfully remove template files}</aside>';
                        }
                    }
                } else {
                    $message = '<aside class=error>{LNG_Unable to complete the transaction}</aside>';
                }
            }
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('div', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-settings">{LNG_Site settings}</span></li>');
            $ul->appendChild('<li><span>{LNG_Template}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-template">'.$this->title.'</h2>'
            ));
            if (!empty($message)) {
                $section->appendChild($message);
            }
            // อ่าน theme ทั้งหมด
            $themes = array();
            $f = opendir($dir);
            while (false !== ($text = readdir($f))) {
                if ($text !== $config->skin && $text !== '.' && $text !== '..') {
                    if (is_dir($dir."/$text") && is_file($dir."/$text/style.css")) {
                        $themes[] = $text;
                    }
                }
            }
            closedir($f);
            // แสดงฟอร์ม
            $section->appendChild(\Index\Template\View::create()->render($request, $dir, $config, $themes));
            return $section->render();
        }
        // 404.html
        return \Index\Error\Controller::page404();
    }
}
