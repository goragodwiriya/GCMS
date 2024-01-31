<?php
/**
 * @filesource modules/document/controllers/init.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Init;

use Gcms\Gcms;
use Gcms\Login;

/**
 * เริ่มต้นใช้งานโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Init Module
     *
     * @param array $modules
     */
    public function init($modules)
    {
        if (!empty($modules)) {
            // login
            $login = Login::isMember();
            $writing = false;
            $rss = array();
            foreach ($modules as $module) {
                // RSS Menu
                $rss[$module->module] = '<link rel="alternate" type="application/rss+xml" title="'.$module->topic.'" href="'.WEB_URL.$module->module.'.rss">';
                // แท็บบทความในเมนูข้อมูลส่วนตัว
                if ($login && self::$cfg->document_can_write && isset($module->can_write) && $login && in_array($login['status'], $module->can_write)) {
                    Gcms::$member_tabs[$module->module] = array(ucfirst($module->module), 'Document\Member\View', 'icon-documents');
                    $writing = true;
                }
            }
            if ($writing) {
                // หน้าสำหรับเขียนบทความ
                Gcms::$member_tabs['documentwrite'] = array(null, 'Document\Write\View');
                // ckeditor
                Gcms::$view->addJavascript(WEB_URL.'ckeditor/ckeditor.js');
            }
            if (!empty($rss)) {
                Gcms::$view->setMetas($rss);
            }
        }
    }
}
