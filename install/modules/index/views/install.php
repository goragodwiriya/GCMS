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

use Kotchasan\Http\Request;

/**
 * ติดตั้ง
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
            $content[] = '<form method=post action=index.php autocomplete=off>';
            $content[] = '<h2>{TITLE}</h2>';
            $content[] = '<p>คุณสามารถเริ่มต้นติดตั้ง GCMS ได้ง่ายๆโดยการตอบคำถามไม่กี่ข้อ เพื่อที่คุณจะได้เป็นเจ้าของ CMS ที่สมบูรณ์แบบ ที่สร้างสรรค์โดยคนไทยทั้งระบบ</p>';
            $content[] = '<p>ก่อนอื่น ลอง<b>ตรวจสอบคุณสมบัติต่างๆของ Server</b> ของคุณตามรายการด้านล่าง ต้องเป็น<span class=correct>สีเขียว</span>ทั้งหมด</p>';
            $content[] = '<ul>';
            $v = version_compare(PHP_VERSION, '5.3.0', '>=');
            $check = array(
                ($v ? 'เวอร์ชั่นของ PHP <b>'.PHP_VERSION.'</b>' : 'ต้องการ PHP เวอร์ชั่น <b>5.3.0</b> ขึ้นไป') => $v ? 'correct' : 'incorrect',
                'PDO mysql Support' => defined('PDO::ATTR_DRIVER_NAME') && in_array('mysql', \PDO::getAvailableDrivers()) ? 'correct' : 'incorrect',
                'MB String Support' => extension_loaded('mbstring') ? 'correct' : 'incorrect',
                'Register Globals <b>Off</b>' => ini_get('register_globals') == false ? 'correct' : 'incorrect',
                'Zlib Compression Support' => extension_loaded('zlib') ? 'correct' : 'incorrect',
                'JSON Support' => function_exists('json_encode') && function_exists('json_decode') ? 'correct' : 'incorrect',
                'XML Support' => extension_loaded('xml') ? 'correct' : 'incorrect',
            );
            $error = false;
            foreach ($check as $text => $class) {
                $error = $class == 'incorrect' || $error;
                $content[] = '<li class='.$class.'>'.$text.'</li>';
            }
            $content[] = '</ul>';
            $content[] = '<p>และแนะนำให้ตั้งค่า Server ตามรายการต่างๆด้านล่างให้เป็นไปตามที่<b>กำหนด</b> (GCMS ยังคงทำงานได้ แต่คุณสมบัติบางอย่างอาจไม่สามารถใช้งานได้)</p>';
            $content[] = '<ul>';
            $content[] = '<li class='.((bool) ini_get('safe_mode') === false ? 'correct' : 'incorrect').'>Safe Mode <b>OFF</b></li>';
            $content[] = '<li class='.((bool) ini_get('file_uploads') === true ? 'correct' : 'incorrect').'>File Uploads <b>ON</b></li>';
            $content[] = '<li class='.((bool) ini_get('magic_quotes_gpc') === false ? 'correct' : 'incorrect').'>Magic Quotes GPC <b>OFF</b></li>';
            $content[] = '<li class='.((bool) ini_get('magic_quotes_runtime') === false ? 'correct' : 'incorrect').'>Magic Quotes Runtime <b>OFF</b></li>';
            $content[] = '<li class='.((bool) ini_get('session.auto_start') === false ? 'correct' : 'incorrect').'>Session Auto Start <b>OFF</b></li>';
            $content[] = '<li class='.(function_exists('zip_open') && function_exists('zip_read') ? 'correct' : 'incorrect').'>Native ZIP support <b>ON</b></li>';
            $content[] = '</ul>';
            if ($error) {
                $content[] = '<p class=warning>Server ของคุณไม่พร้อมสำหรับการติดตั้ง GCMS กรุณาแก้ไขค่าติดตั้งของ Server ที่ถูกทำเครื่องหมาย <span class=incorrect>สีแดง</span> ให้สามารถใช้งานได้ก่อน</p>';
            } else {
                $content[] = '<p>พร้อมแล้วคลิก "ติดตั้ง !" ได้เลย</p>';
                $content[] = '<input type=hidden name=step value=1>';
                $content[] = '<p><input class="button large save" type=submit value="ติดตั้ง !"></p>';
            }
            $content[] = '</form>';
        }
        return (object) array(
            'title' => 'ยินดีต้อนรับสู่การติดตั้ง GCMS เวอร์ชั่น '.self::$cfg->new_version,
            'content' => implode('', $content),
        );
    }
}
