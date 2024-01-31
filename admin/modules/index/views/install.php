<?php
/**
 * @filesource modules/index/views/install.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Install;

use Gcms\Gcms;
use Kotchasan\Html;
use Kotchasan\Language;

/**
 * เพิ่มโมดูลแบบที่สามารถใช้ซ้ำได้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Adminview
{
    /**
     * module=install
     *
     * @param string $type   module หรือ widget
     * @param string $module โมดูลที่ติดตั้ง
     *
     * @return object
     */
    public function render($type, $module)
    {
        $div = Html::create('div', array(
            'class' => 'setup_frm',
            'id' => 'install'
        ));
        if (($type === 'module' && Gcms::$module->findByModule($module) === null) || $type === 'widget') {
            if ($type === 'module') {
                $className = ucfirst($module).'\Admin\Install\Model';
            } elseif ($type === 'widget') {
                $className = 'Widgets\\'.ucfirst($module).'\Models\Install';
            }
            if (isset($className) && class_exists($className)) {
                $div->script("callInstall('".rawurlencode($className)."')");
                $div->add('aside', array(
                    'class' => 'tip',
                    'innerHTML' => Language::get('Module or an extension has not been installed correctly the first time. Please click on the button "Install" below to complete installation before.')
                ));
                $div2 = $div->add('div', array(
                    'class' => 'padding-right-bottom-left'
                ));
                $div2->add('a', array(
                    'class' => 'button ok large',
                    'id' => 'install_btn',
                    'innerHTML' => '<span class=icon-valid>'.Language::get('Install').'</span>'
                ));
            } else {
                $div->add('aside', array(
                    'class' => 'error',
                    'innerHTML' => Language::get('Sorry, cannot find a page called Please check the URL or try the call again.')
                ));
            }
        } else {
            $div->add('aside', array(
                'class' => 'error',
                'innerHTML' => Language::get('Can not install this module. Because this module is already installed. If you want to install this module, you will need to rename installed module to a different name. (This module is to use this name only).')
            ));
        }
        return $div->render();
    }
}
