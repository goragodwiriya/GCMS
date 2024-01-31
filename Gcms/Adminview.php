<?php
/**
 * @filesource Gcms/Adminview.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

use Kotchasan\Language;

/**
 * View base class สำหรับส่วนแอดมินของ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Adminview extends \Kotchasan\View
{
    /**
     * ฟังก์ชั่น แทนที่ query string ด้วยข้อมูลจาก GET และ POST สำหรับส่งต่อไปยัง URL ถัดไป
     * โดยการรับค่าจาก preg_replace
     * คืนค่า URL
     *
     * @param array $f รับค่าจากตัวแปรที่ส่งมาจาก preg_replace มาสร้าง query string
     *
     * @return string
     */
    public static function back($f)
    {
        $query_url = array();
        foreach (self::$request->getQueryParams() as $key => $value) {
            if ($value != '' && $key != 'module') {
                $key = ltrim($key, '_');
                $query_url[$key] = $key.'='.$value;
            }
        }
        foreach (self::$request->getParsedBody() as $key => $value) {
            if ($value != '' && $key != 'module') {
                $key = ltrim($key, '_');
                $query_url[$key] = $key.'='.$value;
            }
        }
        if (isset($f[2])) {
            foreach (explode('&', $f[2]) as $item) {
                if (preg_match('/^(.*)=([^$]{1,})$/', $item, $match)) {
                    if ($match[2] === '0') {
                        unset($query_url[$match[1]]);
                    } else {
                        $query_url[$match[1]] = $item;
                    }
                }
            }
        }
        return WEB_URL.'admin/index.php?'.implode('&amp;', $query_url);
    }

    /**
     * ouput เป็น HTML
     *
     * @param string|null $template HTML Template ถ้าไม่กำหนด (null) จะใช้ index.html
     *
     * @return string
     */
    public function renderHTML($template = null)
    {
        // เนื้อหา
        parent::setContents(array(
            // url สำหรับกลับไปหน้าก่อนหน้า
            '/{BACKURL(\?([a-zA-Z0-9=&\-_@\.]+))?}/e' => '\Gcms\Adminview::back',
            /* ภาษา */
            '/{LNG_([^}]+)}/e' => '\Kotchasan\Language::parse(array(1=>"$1"))',
            /* ภาษา ที่ใช้งานอยู่ */
            '/{LANGUAGE}/' => Language::name(),
            // เวอร์ชั่นของ GCMS
            '/{VERSION}/' => isset(self::$cfg->version) ? self::$cfg->version : ''
        ));
        return parent::renderHTML($template);
    }
}
