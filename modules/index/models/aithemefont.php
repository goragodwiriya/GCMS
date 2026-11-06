<?php
/**
 * @filesource modules/index/models/aithemefont.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Aithemefont;

/**
 * ฟอนต์ของธีมที่ AI Theme Generator สร้าง
 * AI เลือกได้เฉพาะฟอนต์ในรายการนี้ (Google Fonts ที่มีภาษาไทยและน้ำหนัก 300-700)
 * ระบบเป็นผู้เขียน <link> ของ Google Fonts ในเทมเพลตของธีม และตัวแปร --font-family-* เอง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * ชื่อฟอนต์ => กลุ่มฟอนต์สำรอง
     */
    const FONTS = [
        'Noto Sans Thai' => 'sans-serif',
        'Sarabun' => 'sans-serif',
        'Prompt' => 'sans-serif',
        'Kanit' => 'sans-serif',
        'Mitr' => 'sans-serif',
        'IBM Plex Sans Thai' => 'sans-serif',
        'Anuphan' => 'sans-serif',
        'Bai Jamjuree' => 'sans-serif',
        'Chakra Petch' => 'sans-serif',
        'K2D' => 'sans-serif',
        'Krub' => 'sans-serif',
        'Niramit' => 'sans-serif',
        'Athiti' => 'sans-serif',
        'Noto Serif Thai' => 'serif',
        'Taviraj' => 'serif',
        'Trirong' => 'serif',
        'Pridi' => 'serif',
        'Mali' => 'cursive'
    ];

    /**
     * น้ำหนักที่โหลด (ทุกฟอนต์ในรายการมีครบ)
     */
    const WEIGHTS = '300;400;500;600;700';

    /**
     * รายการฟอนต์สำหรับ prompt และตัวเลือกในหน้าแอดมิน
     *
     * @return array [['name' => string, 'style' => string]]
     */
    public static function options()
    {
        $result = [];
        foreach (self::FONTS as $name => $style) {
            $result[] = ['name' => $name, 'style' => $style];
        }

        return $result;
    }

    /**
     * ตรวจฟอนต์ที่ได้จาก AI หรือผู้ใช้ เหลือเฉพาะชื่อที่อยู่ในรายการ
     *
     * @param mixed $fonts ['heading' => string, 'body' => string]
     *
     * @return array|null ['heading' => string, 'body' => string] หรือ null ถ้าไม่เปลี่ยนฟอนต์
     */
    public static function normalize($fonts)
    {
        if (!is_array($fonts)) {
            return null;
        }
        $body = self::find($fonts['body'] ?? '');
        $heading = self::find($fonts['heading'] ?? '');
        if ($body === null && $heading === null) {
            return null;
        }

        return [
            'heading' => $heading ?? $body,
            'body' => $body ?? $heading
        ];
    }

    /**
     * ตัวแปร CSS ของฟอนต์
     *
     * @param array $fonts ค่าจาก normalize()
     *
     * @return array
     */
    public static function declarations(array $fonts)
    {
        return [
            '--font-family-base' => self::stack($fonts['body']),
            '--font-family-heading' => self::stack($fonts['heading'])
        ];
    }

    /**
     * URL ของ Google Fonts ที่โหลดทั้งสองฟอนต์
     *
     * @param array $fonts ค่าจาก normalize()
     *
     * @return string
     */
    public static function stylesheetUrl(array $fonts)
    {
        $families = [];
        foreach (array_unique([$fonts['body'], $fonts['heading']]) as $name) {
            $families[] = 'family='.str_replace(' ', '+', $name).':wght@'.self::WEIGHTS;
        }

        return 'https://fonts.googleapis.com/css2?'.implode('&', $families).'&display=swap';
    }

    /**
     * เขียน <link> ของ Google Fonts ในเทมเพลตทุกไฟล์ของธีมที่โหลดฟอนต์อยู่
     * index.html ที่ไม่มีลิงก์ฟอนต์จะถูกเพิ่มให้ก่อน </head>
     *
     * @param string $dir   โฟลเดอร์ของธีม (ลงท้ายด้วย /)
     * @param array  $fonts ค่าจาก normalize()
     */
    public static function applyToTemplates($dir, array $fonts)
    {
        $link = '<link rel="stylesheet" href="'.htmlspecialchars(self::stylesheetUrl($fonts), ENT_QUOTES).'">';
        $pattern = '#<link\b[^>]*href="https://fonts\.googleapis\.com/css2\?[^"]*"[^>]*>#i';
        foreach (glob($dir.'*.html') ?: [] as $file) {
            $html = (string) file_get_contents($file);
            if (preg_match($pattern, $html)) {
                $html = preg_replace($pattern, $link, $html, 1);
            } elseif (basename($file) === 'index.html' && stripos($html, '</head>') !== false) {
                $html = preg_replace('#</head>#i', '  '.$link."\n</head>", $html, 1);
            } else {
                continue;
            }
            file_put_contents($file, $html);
        }
    }

    /**
     * ฟอนต์หลักที่ธีมโหลดอยู่ (จาก <link> ของ Google Fonts ใน index.html)
     *
     * @param string $dir โฟลเดอร์ของธีม (ลงท้ายด้วย /)
     *
     * @return string ชื่อฟอนต์ หรือ '' ถ้าไม่พบ
     */
    public static function themeFont($dir)
    {
        $file = $dir.'index.html';
        if (is_file($file) && preg_match('#fonts\.googleapis\.com/css2\?family=([^:&"]+)#i', (string) file_get_contents($file), $match)) {
            return str_replace('+', ' ', urldecode($match[1]));
        }

        return '';
    }

    /**
     * ชื่อฟอนต์ตามรายการ (ไม่สนตัวพิมพ์เล็ก/ใหญ่)
     *
     * @param mixed $name
     *
     * @return string|null
     */
    private static function find($name)
    {
        $name = trim((string) $name, " \t\n\r\0\x0B'\"");
        foreach (array_keys(self::FONTS) as $font) {
            if (strcasecmp($font, $name) === 0) {
                return $font;
            }
        }

        return null;
    }

    /**
     * font-family ของฟอนต์พร้อมฟอนต์สำรอง
     *
     * @param string $name
     *
     * @return string
     */
    private static function stack($name)
    {
        $fallbacks = self::FONTS[$name] === 'serif'
            ? ["'Noto Serif Thai'", 'Georgia', 'serif']
            : ["'Noto Sans Thai'", 'Tahoma', 'sans-serif'];
        $stack = array_merge(["'{$name}'"], array_diff($fallbacks, ["'{$name}'"]));

        return implode(', ', $stack);
    }
}
