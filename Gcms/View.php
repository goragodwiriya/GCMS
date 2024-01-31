<?php
/**
 * @filesource Gcms/View.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

use Kotchasan\Language;

/**
 * View base class สำหรับ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\Baseview
{
    /**
     * ลิสต์รายการ breadcrumb
     *
     * @var array
     */
    private $breadcrumbs = array();

    /**
     * เพิ่ม breadcrumb
     *
     * @param string|null $url     ลิงค์ ถ้าเป็นค่า null จะแสดงข้อความเฉยๆ
     * @param string      $menu    ข้อความแสดงใน breadcrumb
     * @param string      $tooltip (option) ทูลทิป
     * @param string      $class   (option) คลาสสำหรับลิงค์นี้
     */
    public function addBreadcrumb($url, $menu, $tooltip = '', $class = '')
    {
        $menu = strip_tags(htmlspecialchars_decode($menu, ENT_NOQUOTES));
        $tooltip = $tooltip == '' ? $menu : $tooltip;
        if ($url) {
            $this->breadcrumbs_jsonld[] = array('@id' => $url, 'name' => $menu);
            $this->breadcrumbs[] = '<li><a class="'.$class.'" href="'.$url.'" title="'.$tooltip.'"><span>'.$menu.'</span></a></li>';
        } else {
            $this->breadcrumbs_jsonld[] = array('name' => $menu);
            $this->breadcrumbs[] = '<li><span class="'.$class.'" title="'.$tooltip.'">'.$menu.'</span></li>';
        }
    }

    /**
     * ฟังก์ชั่นตรวจสอบข้อความว่าง ถ้า $text ไม่ว่างให้เพิ่ม $text ลงใน $array
     *
     * @param string $text   ข้อความที่ต้องการตรวจสอบ
     * @param array  $array  แอเรย์สำหรับรับค่าจาก $text
     * @param string $prefix ข้อความเติมด้านหน้า $text หากมันไม่ว่าง (ไม่มีไม่ต้องระบุ)
     */
    public static function checkEmpty($text, &$array, $prefix = '')
    {
        if ($text != '') {
            $array[] = $prefix.$text;
        }
    }

    /**
     * แปลงวันที่ {DATE 0123456789 d M Y} หรือ {DATE 2016-01-01 12:00:00 d M Y H:i:s}
     * วันที่รูปแบบ mktime ตัวเลขเท่านั้น
     * วันที่รูปแบบ YYYY-mm-dd H:i:s จาก MySQL (จะมีเวลาหรือไม่ก็ได้)
     * ถ้าไม่ได้ระบุรูปแบบ จะใช้ตามรูปแบบของภาษา
     *
     * @param array $matches
     *
     * @return string
     */
    public static function formatDate($matches)
    {
        if (!empty($matches[1])) {
            return \Kotchasan\Date::format($matches[1], isset($matches[4]) ? $matches[4] : null);
        }
        return '';
    }

    /**
     * แสดงผล Widget
     *
     * @param array $matches
     *
     * @return string
     */
    public static function getWidgets($matches)
    {
        $request = array(
            'owner' => strtolower($matches[1])
        );
        if (isset($matches[3])) {
            $request['module'] = $matches[3];
        }
        if (!empty($request['module'])) {
            foreach (explode(';', $request['module']) as $item) {
                if (strpos($item, '=') !== false) {
                    list($key, $value) = explode('=', $item);
                    $request[$key] = $value;
                }
            }
        }
        $className = '\\Widgets\\'.ucfirst(strtolower($matches[1])).'\\Controllers\\Index';
        if (method_exists($className, 'get')) {
            return createClass($className)->get($request);
        }
        return '';
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
            // กรอบ login
            '/{LOGIN}/' => \Index\Login\Controller::init(Login::isMember()),
            // widgets
            '/{WIDGET_([A-Z]+)([_\s]+([^}]+))?}/e' => '\Gcms\View::getWidgets(array(1=>"$1",3=>"$3"))',
            // breadcrumbs
            '/{BREADCRUMBS}/' => implode('', $this->breadcrumbs),
            // ขนาดตัวอักษร
            '/{FONTSIZE}/' => '<a class="font_size small" title="{LNG_change font small}">A<sup>-</sup></a><a class="font_size normal" title="{LNG_change font normal}">A</a><a class="font_size large" title="{LNG_change font large}">A<sup>+</sup></a>',
            // เวอร์ชั่นของ GCMS
            '/{VERSION}/' => isset(self::$cfg->version) ? self::$cfg->version : '',
            // เวลาประมวลผล
            '/{ELAPSED}/' => round(microtime(true) - REQUEST_TIME, 4),
            // จำนวน Query
            '/{QURIES}/' => \Kotchasan\Database\Driver::queryCount(),
            /* ภาษา */
            '/{LNG_([^}]+)}/e' => '\Kotchasan\Language::parse(array(1=>"$1"))',
            /* วันที่ */
            '/{DATE\s([0-9\-]+(\s[0-9:]+)?)?(\s([^}]+))?}/e' => '\Gcms\View::formatDate(array(1=>"$1",4=>"$4"))',
            /* ภาษา ที่ใช้งานอยู่ */
            '/{LANGUAGE}/' => Language::name()
        ));
        // JSON-LD
        if (!empty($this->jsonld)) {
            $this->metas['JsonLd'] = '<script type="application/ld+json">'.json_encode($this->jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'</script>';
        }
        return parent::renderHTML($template);
    }
}
