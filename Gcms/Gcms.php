<?php
/**
 * @filesource Gcms/Gcms.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

use Kotchasan\Language;

/**
 * GCMS utility class
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Gcms extends \Kotchasan\KBase
{
    /**
     * ชื่อสงวนของโมดูล
     *
     * @var array
     */
    public static $MODULE_RESERVE = array(
        'admin',
        'register',
        'forgot',
        'editprofile',
        'sendpm',
        'sendmail',
        'email',
        'member',
        'members',
        'activate',
        'login',
        'dologin'
    );
    /**
     * รายการ breadcrumb ทั้งหมด
     *
     * @var array
     */
    public static $breadcrumbs = array();
    /**
     * tab สำหรับ member
     *
     * @var array
     */
    public static $member_tabs = array(
        'profile' => array('Profile', 'Index\Profile\View', 'icon-profile'),
        'password' => array('Change your password', 'Index\Password\View', 'icon-password'),
        'address' => array('Address details', 'Index\Address\View', 'icon-address')
    );
    /**
     * Menu Model (Frontend)
     *
     * @var \Index\Menu\Controller
     */
    public static $menu;
    /**
     * Menu Model (Frontend)
     *
     * @var \Index\Module\Controller
     */
    public static $module;
    /**
     * รายการเมนูที่สามารถใช้งานได้
     *
     * @var array
     */
    public static $module_menus = array(
        'index' => array(
            'search' => array('{LNG_Search}', '{WEBURL}index.php?module=search', 'search')
        ),
        'member' => array(
            'login' => array('{LNG_Sign in}', '{WEBURL}index.php?module=dologin', 'dologin'),
            'logout' => array('{LNG_Sign out}', '{WEBURL}index.php?action=logout', 'logout'),
            'register' => array('{LNG_Register}', '{WEBURL}index.php?module=register', 'register'),
            'forgot' => array('{LNG_Forgot}', '{WEBURL}index.php?module=forgot', 'forgot'),
            'editprofile' => array('{LNG_Editing your account}', '{WEBURL}index.php?module=editprofile', 'editprofile'),
            'admin' => array('{LNG_Administrator Area}', '{WEBURL}admin/index.php', 'admin')
        )
    );
    /**
     * ข้อมูลเว็บไซต์ สำหรับใส่ลงใน JSON-LD
     *
     * @var array
     */
    public static $site;
    /**
     * รูปแบบของ URL สัมพันธ์กันกับ router_rules
     *
     * @var array
     */
    public static $urls = array(
        'index.php?module={module}-{document}&amp;cat={catid}&amp;id={id}',
        '{module}/{catid}/{id}/{document}.html'
    );
    /**
     * View
     *
     * @var \Gcms\View
     */
    public static $view;

    /**
     * ฟังก์ชั่นตรวจสอบข้อความ ใช้เป็น alias name
     * ตัวพิมพ์เล็ก ลบ {}[]() ออก แทนช่องว่างและอักขระพิเศษด้วย -
     * คืนค่าข้อความ
     *
     * @param string $text ข้อความ
     *
     * @return string
     */
    public static function aliasName($text)
    {
        return preg_replace(array('/[\{\}\[\]\(\)]{1,}/isu', '/[_\:\@~,;\%\-\+\#\r\n\s\"\'<>\.\/\\\?&]{1,}/isu', '/^(_)?(.*?)(_)?$/'), array('', '-', '\\2'), strtolower(trim(strip_tags($text))));
    }

    /**
     * ฟังก์ชั่น ตรวจสอบและทำ serialize สำหรับภาษา โดยรายการที่มีเพียงภาษาเดียว จะกำหนดให้ไม่มีภาษา
     * คืนค่าข้อความที่ทำ serialize แล้ว
     *
     * @param array $array ข้อมูลที่ต้องการจะทำ serialize
     *
     * @return string
     */
    public static function array2Ser($array)
    {
        $new_array = array();
        $l = count($array);
        if ($l > 0) {
            foreach ($array as $i => $v) {
                if ($l == 1 && $i == 0) {
                    $new_array[''] = $v;
                } else {
                    $new_array[$i] = $v;
                }
            }
        }
        return serialize($new_array);
    }

    /**
     * ฟังก์ชั่น ตรวจสอบสถานะที่กำหนด และ แอดมิน
     * คืนค่า true ถ้าสมาชิกที่ login มีสถานะที่กำหนดอยู่ใน $cfg->$key หรือ $cfg[$key]
     *
     * @param array  $login
     * @param object $cfg   ตัวแปรที่มีคีย์ที่ต้องการตรวจสอบเช่น $config
     * @param string $key   คีย์ของ $cfg ที่ต้องการตรวจสอบ, $cfg->$key
     *
     * @return bool
     */
    public static function canConfig($login, $cfg, $key)
    {
        if (isset($login['status'])) {
            if ($login['status'] == 1) {
                return true;
            } elseif (is_array($key)) {
                foreach ($key as $item) {
                    if (isset($cfg->$item) && is_array($cfg->$item) && in_array($login['status'], $cfg->$item)) {
                        return true;
                    }
                }
            } elseif (isset($cfg->$key) && is_array($cfg->$key)) {
                return in_array($login['status'], $cfg->$key);
            }
        }
        return false;
    }

    /**
     * ฟังก์ชั่นแทนที่คำหยาบ
     * คืนค่าข้อความที่ แปลงคำหยาบให้เป็น <em>xxx</em>
     *
     * @param string $detail ข้อความ
     *
     * @return string
     */
    public static function checkRude($detail)
    {
        if (!empty(self::$cfg->wordrude) && is_array(self::$cfg->wordrude)) {
            foreach (self::$cfg->wordrude as $item) {
                $detail = str_replace($item, '<em>'.self::$cfg->wordrude_replace.'</em>', $detail);
            }
        }
        return $detail;
    }

    /**
     * ฟังก์ชั่นสร้าง URL จากโมดูล
     * คืนค่า URL ที่สร้าง
     *
     * @assert ('home', 'ทดสอบ', 1, 1, 'action=login&amp;true') [==] "http://localhost/home/1/1/%E0%B8%97%E0%B8%94%E0%B8%AA%E0%B8%AD%E0%B8%9A.html?action=login&amp;true"
     * @assert ('home', 'ทดสอบ', 1, 1, 'action=login&amp;true', false) [==] "http://localhost/home/1/1/ทดสอบ.html?action=login&amp;true"
     * @assert ('home', 'ทดสอบ', 1, 1, 'action=login&amp;true') [==] "http://localhost/index.php?module=home-%E0%B8%97%E0%B8%94%E0%B8%AA%E0%B8%AD%E0%B8%9A&amp;cat=1&amp;id=1&amp;action=login&amp;true" [[self::$cfg->module_url = 0]]
     * @assert ('home', 'ทดสอบ', 1, 1, 'action=login&amp;true', false) [==] "http://localhost/index.php?module=home-ทดสอบ&amp;cat=1&amp;id=1&amp;action=login&amp;true" [[self::$cfg->module_url = 0]]
     *
     * @param string $module   URL ชื่อโมดูล
     * @param string $document (option)
     * @param int    $catid    id ของหมวดหมู่ (default 0)
     * @param int    $id       (option) id ของข้อมูล (default 0)
     * @param string $query    (option) query string อื่นๆ (default ค่าว่าง)
     * @param bool   $encode   (option) true=เข้ารหัสด้วย rawurlencode ด้วย (default true)
     *
     * @return string
     */
    public static function createUrl($module, $document = '', $catid = 0, $id = 0, $query = '', $encode = true)
    {
        $patt = array();
        $replace = array();
        if (empty($document)) {
            $patt[] = '/[\/-]{document}/';
            $replace[] = '';
        } else {
            $patt[] = '/{document}/';
            $replace[] = $encode ? rawurlencode($document) : $document;
        }
        $patt[] = '/{module}/';
        $replace[] = $encode ? rawurlencode($module) : $module;
        if (empty($catid)) {
            $patt[] = '/((cat={catid}&amp;)|([\/-]{catid}))/';
            $replace[] = '';
        } else {
            $patt[] = '/{catid}/';
            $replace[] = (int) $catid;
        }
        if (empty($id)) {
            $patt[] = '/(((&amp;|\?)id={id})|([\/-]{id}))/';
            $replace[] = '';
        } else {
            $patt[] = '/{id}/';
            $replace[] = (int) $id;
        }
        $link = preg_replace($patt, $replace, self::$urls[self::$cfg->module_url]);
        if (!empty($query)) {
            $link .= (strpos($link, '?') === false ? '?' : '&amp;').$query;
        }
        return WEB_URL.$link;
    }

    /**
     * ฟังก์ชั่น ทำ highlight ข้อความ
     * คืนค่าข้อความ ข้อความที่ highlight จะอยู่ภายใต้ tag mark
     *
     * @param string $text   ข้อความ
     * @param string $needle ข้อความที่ต้องการทำ highlight
     *
     * @return string
     */
    public static function doHighlight($text, $needle)
    {
        $newtext = '';
        $i = -1;
        $len_needle = mb_strlen($needle);
        while (mb_strlen($text) > 0) {
            $i = mb_stripos($text, $needle, $i + 1);
            if ($i == false) {
                $newtext .= $text;
                $text = '';
            } else {
                $a = self::lastIndexOf($text, '>', $i) >= self::lastIndexOf($text, '<', $i);
                $a = $a && (self::lastIndexOf($text, '}', $i) >= self::lastIndexOf($text, '{LNG_', $i));
                $a = $a && (self::lastIndexOf($text, '/script>', $i) >= self::lastIndexOf($text, '<script', $i));
                $a = $a && (self::lastIndexOf($text, '/style>', $i) >= self::lastIndexOf($text, '<style', $i));
                if ($a) {
                    $newtext .= mb_substr($text, 0, $i).'<mark>'.mb_substr($text, $i, $len_needle).'</mark>';
                    $text = mb_substr($text, $i + $len_needle);
                    $i = -1;
                }
            }
        }
        return $newtext;
    }

    /**
     * ฟังก์ชั่นคืนค่าข้อความ placeholder ของช่อง login
     *
     * @return string
     */
    public static function getLoginPlaceholder()
    {
        $login_fields = array(
            'email' => '{LNG_Email}',
            'username' => '{LNG_Username}',
            'phone1' => '{LNG_Phone}',
            'idcard' => '{LNG_Identification No.}'
        );
        $placeholder = array();
        foreach (self::$cfg->login_fields as $item) {
            if (isset($login_fields[$item])) {
                $placeholder[] = $login_fields[$item];
            }
        }
        return Language::trans(implode('/', $placeholder));
    }

    /**
     * ฟังก์ชั่น highlight ข้อความค้นหา
     * คืนค่าข้อความ
     *
     * @param string $text   ข้อความ
     * @param string $search ข้อความค้นหา แยกแต่ละคำด้วย ,
     *
     * @return string
     */
    public static function highlightSearch($text, $search)
    {
        foreach (explode(' ', $search) as $i => $q) {
            if ($q != '') {
                $text = self::doHighlight($text, $q);
            }
        }
        return $text;
    }

    /**
     * ฟังก์ชั่น HTML highlighter
     * ทำ highlight ข้อความส่วนที่เป็นโค้ด
     * จัดการแปลง BBCode
     * แปลงข้อความ http เป็นลิงค์
     * คืนค่าข้อความ
     *
     * @param string $detail  ข้อความ
     * @param bool   $canview true จะแสดงข้อความเตือน 'ยังไม่ได้เข้าระบบ' หากไม่ได้เข้าระบบ สำหรับส่วนที่อยู่ในกรอบ code
     *
     * @return string
     */
    public static function highlighter($detail, $canview)
    {
        $detail = preg_replace_callback('/\[([uo]l)\](.*)\[\/\\1\]/is', function ($match) {
            return '<'.$match[1].'><li>'.preg_replace('/<br(\s\/)?>/is', '</li><li>', $match[2]).'</li></'.$match[1].'>';
        }, $detail);
        $patt[] = '/\[(i|dfn|b|strong|u|em|ins|del|sub|sup|small|big)\](.*)\[\/\\1\]/is';
        $replace[] = '<\\1>\\2</\\1>';
        $patt[] = '/(&lt;(script|style)(&gt;|\s(.*?)&gt;)([^\[]+)&lt;\/\\2&gt;)/uis';
        $replace[] = '<span class=\\2>\\1</span>';
        $patt[] = '#(&lt;[\/]?([\!a-z]+)(.*?)&gt;)#';
        $replace[] = '<span class=html>\\1</span>';
        $patt[] = '/([^:])(\/\/\s[^\r\n]+)/';
        $replace[] = '\\1<span class=comment>\\2</span>';
        $patt[] = '/(\/\*(.*?)\*\/)/s';
        $replace[] = '<span class=comment>\\1</span>';
        $patt[] = '/(&lt;!--(.*?)--&gt;)/uis';
        $replace[] = '<span class=comment>\\1</span>';
        $patt[] = '/\[color=([#a-z0-9]+)\]/i';
        $replace[] = '<span style="color:\\1">';
        $patt[] = '/\[\/(color|size)\]/i';
        $replace[] = '</span>';
        $patt[] = '#\[center\](.*?)\[\/center\]#is';
        $replace[] = '<div class=center>\\1</div>';
        $patt[] = '#\[img\](http[^\[\"\']+)\[\/img\]#is';
        $replace[] = '<figure><img src="\\1" alt=""></figure>';
        $patt[] = '#\[url\]([^\"\'\[]+)\[\/url\]#is';
        $replace[] = '<a href="\\1" target="_blank">\\1</a>';
        $patt[] = '/\[url=(ftp|https?):\/\/([^\"\'\]]+)\]/iU';
        $replace[] = '<a href="\\1://\\2" target="_blank">';
        $patt[] = '/\[url=(\/)?([^\"\'\]]+)\]/iU';
        $replace[] = '<a href="'.WEB_URL.'\\2" target="_blank">';
        $patt[] = '/\[\/url\]/iU';
        $replace[] = '</a>';
        $patt[] = '/\[quote(\s+q=[0-9]+)?\]/i';
        $replace[] = '<blockquote><b>'.Language::replace('Quotations by :name', array(':name' => Language::get('Topic'))).'</b>';
        $patt[] = '/\[quote\s+r=([0-9]+)\]/i';
        $replace[] = '<blockquote><b>'.Language::replace('Quotations by :name', array(':name' => Language::get('Comment'))).' <em>#\\1</em></b>';
        $patt[] = '/\[\/quote\]/i';
        $replace[] = '</blockquote>';
        $patt[] = '#\[code(=([a-z]{1,}))?\](.*?)\[\/code\]#is';
        $replace[] = $canview ? '<code><a class="copytoclipboard notext" title="'.Language::get('copy to clipboard').'"><span class="icon-copy"></span></a><div class="content-code \\2">\\3</div></code>' : '<code class="content-code">'.Language::get('Can not view this content').'</code>';
        $patt[] = '/\[search\](.*)\[\/search\]/iU';
        $replace[] = '<a href="'.WEB_URL.'index.php?module=search&amp;q=\\1">\\1</a>';
        $patt[] = '/\[google\](.*?)\[\/google\]/iU';
        $replace[] = '<a class="googlesearch" href="http://www.google.co.th/search?q=\\1&amp;&meta=lr%3Dlang_th" target="_blank">\\1</a>';
        $patt[] = '/([^["]]|\r|\n|\s|\t|^)((ftp|https?):\/\/([a-z0-9\.\-_]+)\/([^\s<>\"\']{1,})([^\s<>\"\']{20,20}))/i';
        $replace[] = '\\1<a href="\\2" target="_blank">\\3://\\4/...\\6</a>';
        $patt[] = '/([^["]]|\r|\n|\s|\t|^)((ftp|https?):\/\/([^\s<>\"\']+))/i';
        $replace[] = '\\1<a href="\\2" target="_blank">\\2</a>';
        $patt[] = '/(<a[^>]+>)(https?:\/\/[^\%<]+)([\%][^\.\&<]+)([^<]{5,})(<\/a>)/i';
        $replace[] = '\\1\\2...\\4\\5';
        $patt[] = '/\[youtube\]([a-z0-9-_]+)\[\/youtube\]/i';
        $replace[] = '<div class="youtube"><iframe src="//www.youtube.com/embed/\\1?wmode=transparent" allowfullscreen></iframe></div>';
        return preg_replace($patt, $replace, $detail);
    }

    /**
     * ฟังก์ชั่น แปลง html เป็น text
     * สำหรับตัด tag หรือเอา BBCode ออกจากเนื้อหาที่เป็น HTML ให้เหลือแต่ข้อความล้วน
     * คืนค่าข้อความ
     *
     * @param string $text ข้อความ
     *
     * @return string
     */
    public static function html2txt($text, $len = 0)
    {
        $patt = array();
        $replace = array();
        // ตัด style
        $patt[] = '@<style[^>]*?>.*?</style>@siu';
        $replace[] = '';
        // ตัด comment
        $patt[] = '@<![\s\S]*?--[ \t\n\r]*>@u';
        $replace[] = '';
        // ตัด tag
        $patt[] = '@<[\/\!]*?[^<>]*?>@iu';
        $replace[] = '';
        // ตัด keywords
        $patt[] = '/{(WIDGET|LNG)_[a-zA-Z0-9_]+}/su';
        $replace[] = '';
        // ลบ BBCode
        $patt[] = '/(\[code(.+)?\]|\[\/code\]|\[ex(.+)?\])/ui';
        $replace[] = '';
        // ลบ BBCode ทั่วไป [b],[i]
        $patt[] = '/\[([a-z]+)([\s=].*)?\](.*?)\[\/\\1\]/ui';
        $replace[] = '\\3';
        $replace[] = ' ';
        // ตัดตัวอักษรที่ไม่ต้องการออก
        $patt[] = '/(&amp;|&quot;|&nbsp;|[_\(\)\-\+\r\n\s\"\'<>\.\/\\\?&\{\}]){1,}/isu';
        $replace[] = ' ';
        $text = trim(preg_replace($patt, $replace, $text));
        if ($len > 0) {
            $text = \Kotchasan\Text::cut($text, $len);
        }
        return $text;
    }

    /**
     * อ่านภาษาที่ติดตั้งตามลำดับการตั้งค่า
     *
     * @return array
     */
    public static function installedLanguage()
    {
        $languages = array();
        foreach (self::$cfg->languages as $item) {
            $languages[$item] = $item;
        }
        foreach (Language::installedLanguage() as $item) {
            $languages[$item] = $item;
        }
        return array_keys($languages);
    }

    /**
     * ฟังก์ชั่น แปลงข้อความสำหรับการ quote
     * คืนค่าข้อความ
     *
     * @param string $text ข้อความ
     * @param bool   $u    true=ถอดรหัสอักขระพิเศษด้วย (default false)
     *
     * @return string
     */
    public static function quote($text, $u = false)
    {
        $text = preg_replace('/<br(\s\/)?>/isu', '', $text);
        if ($u) {
            $text = str_replace(array('&lt;', '&gt;', '&#92;', '&nbsp;', '&#x007B;', '&#x007D;'), array('<', '>', '\\', ' ', '{', '}'), $text);
        }
        return $text;
    }

    /**
     * ฟังก์ชั่น อ่านหมวดหมู่ในรูป serialize ตามภาษาที่เลือก
     * คืนค่าข้อความ
     *
     * @param mixed  $datas ข้อความ serialize
     * @param string $key   (optional) ถ้า $datas เป็น array ต้องระบุ $key ด้วย
     *
     * @return string
     */
    public static function ser2Str($datas, $key = '')
    {
        if (is_array($datas)) {
            $datas = isset($datas[$key]) ? $datas[$key] : '';
        }
        if (!empty($datas)) {
            $datas = @unserialize($datas);
            if (is_array($datas)) {
                $lng = Language::name();
                $datas = isset($datas[$lng]) ? $datas[$lng] : (isset($datas['']) ? $datas[''] : '');
            }
        }
        return $datas;
    }

    /**
     * ฟังก์ชั่นแสดงเนื้อหา
     *
     * @param string $detail      ข้อความ
     * @param bool   $canview     true จะแสดงข้อความเตือน 'ยังไม่ได้เข้าระบบ' หากไม่ได้เข้าระบบ สำหรับส่วนที่อยู่ในกรอบ code
     * @param bool   $rude        (optional) true=ตรวจสอบคำหยาบด้วย (default true)
     * @param bool   $convert_tab (optional) true=เปลี่ยน tab เป็นช่องว่าง 4 ตัวอักษร (default false)
     *
     * @return string
     */
    public static function showDetail($detail, $canview, $rude = true, $convert_tab = false)
    {
        if ($convert_tab) {
            $detail = preg_replace('/[\t]/', '&nbsp;&nbsp;&nbsp;&nbsp;', $detail);
        }
        if ($rude) {
            return self::highlighter(self::checkRude($detail), $canview);
        } else {
            return self::highlighter($detail, $canview);
        }
    }

    /**
     * คืนค่าลิงค์รูปแบบโทรศัพท์
     *
     * @param string $phone_number
     *
     * @return string
     */
    public static function showPhone($phone_number)
    {
        if (preg_match('/^([0-9\-\s]{9,})(.*)$/', $phone_number, $match)) {
            return '<a href="tel:'.trim($match[1]).'">'.$phone_number.'</a>';
        }
        return $phone_number;
    }

    /**
     * ฟังก์ชั่น แสดง ip แบบซ่อนหลักหลัง ถ้าเป็น admin จะแสดงทั้งหมด
     * คืนค่า ที่อยู่ IP ที่แปลงแล้ว
     *
     * @param string $ip    ที่อยู่ IP ที่ต้องการแปลง (IPV4)
     * @param array  $login
     *
     * @return string
     */
    public static function showip($ip, $login)
    {
        if ($login && $login['status'] != 1 && preg_match('/([0-9]+\.[0-9]+\.)([0-9\.]+)/', $ip, $ips)) {
            return $ips[1].preg_replace('/[0-9]/', 'x', $ips[2]);
        } else {
            return $ip;
        }
    }

    /**
     * คืนค่าชื่อไอคอนสำหรับช่อง username
     * เข้าระบบได้ทั้ง email และ phone1 คืนค่า user
     * เข้าระบบได้เฉพาะ email คืนค่า email
     * เข้าระบบได้เฉพาะ phone1 คืนค่า phone
     *
     * @return string
     */
    public static function usernameIcon()
    {
        $email = in_array('email', self::$cfg->login_fields);
        $phone = in_array('phone1', self::$cfg->login_fields);
        if ($email && $phone) {
            return 'user';
        } else {
            return $email ? 'email' : 'phone';
        }
    }

    /**
     * ฟังก์ชั่น ค้นหาข้อความย้อนหลัง
     * คืนค่าตำแหน่งของตัวอักษรที่พบ ตัวแรกคือ หากไม่พบคืนค่า -1
     *
     * @param string $text   ข้อความ
     * @param string $needle ข้อความค้นหา
     * @param int    $offset ตำแหน่งเริ่มต้นที่ต้องการค้นหา
     *
     * @return int
     */
    private static function lastIndexOf($text, $needle, $offset)
    {
        $pos = mb_strripos(mb_substr($text, 0, $offset), $needle);
        return $pos == false ? -1 : $pos;
    }
}
