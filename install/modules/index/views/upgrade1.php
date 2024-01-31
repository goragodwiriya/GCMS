<?php
/**
 * @filesource modules/index/views/upgrade1.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade1;

use Kotchasan\File;
use Kotchasan\Http\Request;

/**
 * อัปเกรด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * step 1
     *
     * @return string
     */
    public function render(Request $request)
    {
        $content = array();
        if (defined('INSTALL')) {
            $content[] = '<h2>{TITLE}</h2>';
            $content[] = '<p>ไฟล์และโฟลเดอร์ทั้งหมดตามรายการด้านล่างต้องถูกสร้างขึ้น และกำหนดค่าให้สามารถเขียนได้</p>';
            $content[] = '<ul>';
            $folders = array();
            $folders[0] = ROOT_PATH.DATA_FOLDER;
            // rename ไดเร็คทอรี่ข้อมูลเดิม
            $mktime = time();
            if (is_dir($folders[0]) && !is_dir(ROOT_PATH.$mktime.'datas/')) {
                $f = @rename($folders[0], ROOT_PATH.$mktime.'datas/');
                if ($f === false) {
                    $error = true;
                    $content[] = '<li class=incorrect>โฟลเดอร์ <strong>'.dirname(ROOT_PATH).'</strong> <em>ไม่สามารถเขียนได้</em> กรุณาปรับ chmod ให้สามารถเขียนได้ หรือ เป็น 777 ชั่วคราว</li>';
                }
            }
            $dir = ROOT_PATH.'modules/';
            $f = opendir($dir);
            while (false !== ($text = readdir($f))) {
                if ($text != '.' && $text != '..' && $text != 'index' && $text != 'css' && $text != 'js') {
                    if (is_dir($dir.$text)) {
                        $folders[] = $folders[0].$text.'/';
                    }
                }
            }
            closedir($f);
            $folders[] = $folders[0].'counter/';
            $folders[] = $folders[0].'file/';
            $folders[] = $folders[0].'image/';
            $folders[] = $folders[0].'cache/';
            $folders[] = $folders[0].'logs/';
            $folders[] = ROOT_PATH.'settings/';
            $folders[] = ROOT_PATH.'language/';
            foreach ($folders as $folder) {
                File::makeDirectory($folder, 0755);
                if (is_writable($folder)) {
                    $content[] = '<li class=correct>โฟลเดอร์ <strong>'.str_replace(ROOT_PATH, '', $folder).'</strong> <i>สามารถใช้งานได้</i></li>';
                } else {
                    $error = true;
                    $content[] = '<li class=incorrect>โฟลเดอร์ <strong>'.str_replace(ROOT_PATH, '', $folder).'</strong> <em>ไม่สามารถเขียนหรือสร้างได้</em> กรุณาสร้างโฟลเดอร์นี้และปรับ chmod ให้เป็น 757 ด้วยตัวเอง</li>';
                }
            }
            if (is_dir(ROOT_PATH.$mktime.'datas/')) {
                self::copyDirectory(ROOT_PATH.$mktime.'datas/', $folders[0]);
                File::removeDirectory(ROOT_PATH.$mktime.'datas/');
            }
            $files = array();
            $files[] = ROOT_PATH.'.htaccess';
            $files[] = ROOT_PATH.'robots.txt';
            $files[] = ROOT_PATH.'settings/config.php';
            $files[] = ROOT_PATH.'settings/database.php';
            foreach ($files as $file) {
                if (!is_file($file)) {
                    $f = @fopen($file, 'wb');
                    if ($f) {
                        fclose($f);
                    }
                }
                if (is_writable($file)) {
                    $content[] = '<li class=correct>ไฟล์ <strong>'.str_replace(ROOT_PATH, '', $file).'</strong> <i>สามารถใช้งานได้</i></li>';
                } else {
                    $error = true;
                    $content[] = '<li class=incorrect>ไฟล์ <strong>'.str_replace(ROOT_PATH, '', $file).'</strong> <em>ไม่สามารถเขียนหรือสร้างได้</em> กรุณาสร้างไฟล์นี้และปรับ chmod ให้เป็น 755 ด้วยตัวเอง</li>';
                }
            }
            $content[] = '</ul>';
            $content[] = '<p><a href="index.php?step=1" class="button large pink">ตรวจสอบใหม่</a>&nbsp;<a href="index.php?step=2" class="button large save">ดำเนินการต่อ</a></p>';
        }
        return (object) array(
            'title' => 'ตรวจสอบไฟล์ โฟลเดอร์ และนำเข้าข้อมูลเดิม',
            'content' => implode('', $content),
        );
    }

    /**
     * @param $dir
     * @param $todir
     */
    public static function copyDirectory($dir, $todir)
    {
        $f = opendir($dir);
        while (false !== ($text = readdir($f))) {
            if ($text !== '.' && $text !== '..' && $text !== 'cache' && $text !== 'logs' && $text !== '_thumb' && $text !== 'debug.php' && $text !== 'error_log.php') {
                if (is_dir($dir.$text)) {
                    File::makeDirectory($todir.$text.'/');
                    self::copyDirectory($dir.$text.'/', $todir.$text.'/');
                } else {
                    copy($dir.$text, $todir.$text);
                }
            }
        }
        closedir($f);
    }
}
