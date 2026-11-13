<?php
/**
 * @filesource modules/index/models/aithemecolor.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Aithemecolor;

/**
 * สีของธีมที่ AI Theme Generator สร้าง
 * - แปลงค่าสี CSS (hex, rgb(), hsl(), var()) เป็นค่า RGB
 * - ตรวจ contrast ของคู่สีตัวอักษร/พื้นหลังตามเกณฑ์ WCAG 2.x และปรับสีตัวอักษรที่อ่านยาก
 * - ดึงสีหลักจากโลโก้ของเว็บไซต์
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * คู่สีที่ตรวจ: [ชื่อที่แสดง, ตัวอักษร, พื้นหลัง, contrast ขั้นต่ำ, ปรับสีตัวอักษรเองได้]
     * 4.5 = ข้อความปกติ (WCAG AA), 3.0 = ข้อความใหญ่ ตัวหนา และปุ่ม
     * คู่ที่ปรับเองไม่ได้ (สีปุ่ม/ลิงก์ = สีหลักของแบรนด์) จะรายงานเป็นคำเตือนเท่านั้น
     */
    const PAIRS = [
        ['Body text', '--color-text', '--color-page-bg', 4.5, true],
        ['Text on cards', '--color-text', '--color-surface-bg', 4.5, true],
        ['Secondary text', '--color-text-secondary', '--color-page-bg', 4.5, true],
        ['Muted text', '--color-text-muted', '--color-page-bg', 3.0, true],
        ['Header text', '--header-text', '--header-bg', 4.5, true],
        ['Footer text', '--footer-text', '--footer-bg', 4.5, true],
        ['Footer headings', '--footer-heading', '--footer-bg', 4.5, true],
        ['Highlighted menu', '--menu-highlight-text', '--menu-highlight-bg', 4.5, true],
        ['Selected menu', '--menu-select-text', '--menu-select-bg', 4.5, true],
        ['Mobile menu', '--topmenu-mobile-text', '--topmenu-mobile-bg', 4.5, true],
        ['Top menu', '--topmenu-text-color', '--topmenu-bg', 4.5, true],
        ['Side menu', '--sidemenu-text-color', '--sidemenu-button-bg', 4.5, true],
        ['Top menu highlight', '--topmenu-highlight-text', '--topmenu-highlight-bg', 4.5, true],
        ['Top menu selected', '--topmenu-select-text', '--topmenu-select-bg', 4.5, true],
        ['Side menu highlight', '--sidemenu-highlight-text', '--sidemenu-highlight-bg', 4.5, true],
        ['Side menu selected', '--sidemenu-select-text', '--sidemenu-select-bg', 4.5, true],
        ['Text on primary color', '--color-primary-text', '--color-primary-main', 3.0, true],
        ['Button label', '--color-white', '--color-primary', 3.0, false],
        ['Links', '--color-primary', '--color-page-bg', 3.0, false]
    ];

    /**
     * token หลักที่ใช้เทียบสีของธีมต้นแบบกับชุดสีใหม่ (ดู anchors() / recolor())
     */
    const ANCHORS = [
        '--color-primary', '--color-primary-hover', '--color-primary-dark', '--color-primary-light',
        '--color-primary-main', '--color-accent', '--color-accent-hover', '--header-bg', '--footer-bg',
        '--footer-heading', '--color-page-bg', '--color-surface-bg', '--home-bg', '--color-border', '--color-text'
    ];

    /**
     * คู่สี เดิม → ใหม่ ของ token หลัก ใช้เปลี่ยนสีที่ธีมต้นแบบกำหนดไว้เอง
     * (token เฉพาะธีมและสีที่เขียนตายตัวใน rule) ให้เข้ากับชุดสีใหม่
     *
     * @param array $baseVars ตัวแปรของธีมต้นแบบ
     * @param array $newVars  ตัวแปรหลังใส่ชุดสีใหม่
     *
     * @return array
     */
    public static function anchors(array $baseVars, array $newVars)
    {
        $anchors = [];
        foreach (self::ANCHORS as $name) {
            $from = self::resolveColor($name, $baseVars);
            $to = self::resolveColor($name, $newVars);
            if ($from === null || $to === null || $from[3] < 1 || $to[3] < 1) {
                continue;
            }
            $anchors[] = [
                'brand' => strpos($name, '--color-primary') === 0 || strpos($name, '--color-accent') === 0,
                'from' => $from,
                'to' => $to,
                'fromHsl' => self::rgbToHsl($from),
                'toHsl' => self::rgbToHsl($to)
            ];
        }

        return $anchors;
    }

    /**
     * สีของธีมต้นแบบในชุดสีใหม่
     * - สีกลาง (ขาว ดำ เทา) คงเดิม
     * - สีที่ใกล้ token หลักตัวใด (สีหลัก, header, footer, ...) = สีใหม่ของ token นั้น โดยคงความต่างของความสว่างไว้
     *   (เช่น สีเมนูที่ถูกชี้ซึ่งเข้มกว่าสีหลักเล็กน้อย ยังเข้มกว่าสีหลักใหม่เท่าเดิม)
     * - สีอื่นหมุน hue และปรับความสดตามที่ token ที่ใกล้ที่สุดเปลี่ยนไป
     *
     * @param array $color   [r, g, b, a]
     * @param array $anchors ค่าจาก anchors()
     *
     * @return array [r, g, b, a] — array เดิมถ้าไม่เปลี่ยน
     */
    public static function recolor(array $color, array $anchors)
    {
        $hsl = self::rgbToHsl($color);
        if ($hsl[1] < 0.18 || $hsl[2] < 0.06 || $hsl[2] > 0.96) {
            return $color;
        }
        // สีของแบรนด์ในธีมมักแตกออกจากสีหลัก/สีเน้น — เทียบกลุ่มนั้นก่อน แล้วค่อยเทียบ token อื่น (header, footer, ...)
        $best = null;
        $bestDistance = INF;
        foreach ([true, false] as $brandOnly) {
            foreach ($anchors as $anchor) {
                if ($anchor['fromHsl'][1] < 0.18 || ($brandOnly && !$anchor['brand'])) {
                    continue;
                }
                $distance = self::distance($color, $anchor['from']);
                if ($distance < $bestDistance) {
                    $best = $anchor;
                    $bestDistance = $distance;
                }
            }
            if ($bestDistance <= 120) {
                break;
            }
        }
        if ($best === null) {
            return $color;
        }
        if ($bestDistance <= 90) {
            list($h, $s, $l) = $best['toHsl'];
            $l = $l + ($hsl[2] - $best['fromHsl'][2]);
        } else {
            $h = $hsl[0] + ($best['toHsl'][0] - $best['fromHsl'][0]);
            $s = $hsl[1] * ($best['toHsl'][1] / max(0.01, $best['fromHsl'][1]));
            $l = $hsl[2];
        }
        $rgb = self::hslToRgb($h, max(0, min(1, $s)), max(0, min(1, $l)));
        $rgb[] = isset($color[3]) ? (float) $color[3] : 1.0;

        return $rgb;
    }

    /**
     * แทนสีทุกค่าในค่า CSS หนึ่งค่า (hex, rgb(), rgba(), hsl(), hsla()) ด้วยสีจาก recolor()
     *
     * @param string $value
     * @param array  $anchors
     *
     * @return string
     */
    public static function recolorValue($value, array $anchors)
    {
        return preg_replace_callback('/#[0-9a-f]{3,8}\b|(?:rgba?|hsla?)\([^()]*\)/i', function ($match) use ($anchors) {
            $color = self::parse($match[0]);
            if ($color === null) {
                return $match[0];
            }
            $new = self::recolor($color, $anchors);

            return $new === $color ? $match[0] : self::format($new);
        }, (string) $value);
    }

    /**
     * สีเป็นค่า CSS: #rrggbb หรือ rgba() เมื่อโปร่งแสง
     *
     * @param array $rgba
     *
     * @return string
     */
    public static function format(array $rgba)
    {
        $alpha = isset($rgba[3]) ? (float) $rgba[3] : 1.0;
        if ($alpha >= 1) {
            return self::toHex($rgba);
        }

        return sprintf('rgba(%d, %d, %d, %s)', round($rgba[0]), round($rgba[1]), round($rgba[2]), rtrim(rtrim(sprintf('%.3f', $alpha), '0'), '.'));
    }

    /**
     * ตรวจ contrast ของคู่สีใน PAIRS และปรับสีตัวอักษรที่ไม่ผ่าน
     *
     * @param array $declarations ตัวแปร :root ของธีมใหม่ (ชื่อ => ค่า) — สีที่ปรับจะถูกเขียนทับในนี้
     * @param array $baseVars     ตัวแปร :root ที่มีผลอยู่ก่อนหน้า (Now.js + gcms.css + ธีมต้นแบบ)
     *
     * @return array ['declarations' => array, 'report' => array]
     */
    public static function audit(array $declarations, array $baseVars)
    {
        $original = array_merge($baseVars, $declarations);
        $vars = $original;
        // ตัวแปรเดียวอยู่ได้หลายคู่ (--color-text บนพื้นหน้าเว็บและบนการ์ด)
        // ปรับวนจนทุกคู่ผ่าน แล้วค่อยรายงานค่าสุดท้ายเทียบกับค่าเดิม
        for ($pass = 0; $pass < 3; ++$pass) {
            $changed = false;
            foreach (self::PAIRS as list(, $fgName, $bgName, $minimum, $fixable)) {
                $pair = $fixable ? self::measure($fgName, $bgName, $vars) : null;
                if ($pair !== null && $pair['ratio'] < $minimum) {
                    $hex = self::toHex(self::fixForeground($pair['fg'], $pair['bg'], $minimum));
                    $declarations[$fgName] = $hex;
                    $vars[$fgName] = $hex;
                    $changed = true;
                }
            }
            if (!$changed) {
                break;
            }
        }
        $report = [];
        foreach (self::PAIRS as list($label, $fgName, $bgName, $minimum)) {
            $before = self::measure($fgName, $bgName, $original);
            $after = self::measure($fgName, $bgName, $vars);
            if ($before === null || $after === null) {
                // ค่าที่ไม่ใช่สีเดี่ยว (gradient, รูปภาพ) ตรวจไม่ได้
                continue;
            }
            $item = [
                'label' => $label,
                'foreground' => $fgName,
                'background' => $bgName,
                'from' => self::toHex($before['fg']),
                'on' => self::toHex($after['bg']),
                'ratio' => round($after['ratio'], 2),
                'minimum' => $minimum,
                'status' => $after['ratio'] < $minimum ? 'warn' : 'pass'
            ];
            if (self::toHex($before['fg']) !== self::toHex($after['fg'])) {
                $item['status'] = 'fixed';
                $item['to'] = self::toHex($after['fg']);
                $item['before'] = round($before['ratio'], 2);
            }
            $report[] = $item;
        }

        return [
            'declarations' => $declarations,
            'report' => $report
        ];
    }

    /**
     * สีจริงของคู่ตัวอักษร/พื้นหลัง และ contrast
     *
     * @param string $fgName
     * @param string $bgName
     * @param array  $vars
     *
     * @return array|null ['fg' => array, 'bg' => array, 'ratio' => float]
     */
    private static function measure($fgName, $bgName, array $vars)
    {
        $fg = self::resolveColor($fgName, $vars);
        $bg = self::resolveColor($bgName, $vars);
        if ($fg === null || $bg === null) {
            return null;
        }
        $page = self::resolveColor('--color-page-bg', $vars);
        $page = $page === null ? [255, 255, 255, 1.0] : self::flatten($page, [255, 255, 255, 1.0]);
        $bg = self::flatten($bg, $page);
        $fg = self::flatten($fg, $bg);

        return [
            'fg' => $fg,
            'bg' => $bg,
            'ratio' => self::contrast($fg, $bg)
        ];
    }

    /**
     * อ่านตัวแปรใน :root ทุกบล็อกของ CSS (บล็อกหลังทับบล็อกก่อน)
     *
     * @param string $css
     *
     * @return array ชื่อตัวแปร (ตัวเล็ก) => ค่า
     */
    public static function rootVariables($css)
    {
        $vars = [];
        $css = preg_replace('#/\*.*?\*/#s', '', (string) $css);
        // บล็อกใน @media/@supports (เช่น dark mode) ไม่ใช่ค่าปกติของหน้าเว็บ — ตัดทิ้งทั้งบล็อก
        $plain = '';
        $depth = 0;
        $skipDepth = -1;
        $length = strlen($css);
        for ($i = 0; $i < $length; ++$i) {
            $char = $css[$i];
            if ($char === '@' && $depth === 0 && $skipDepth < 0) {
                $semicolon = strpos($css, ';', $i);
                $brace = strpos($css, '{', $i);
                if ($brace === false || ($semicolon !== false && $semicolon < $brace)) {
                    $i = $semicolon === false ? $length : $semicolon;
                    continue;
                }
                $skipDepth = 0;
            }
            if ($char === '{') {
                ++$depth;
            } elseif ($char === '}') {
                $depth = max(0, $depth - 1);
                if ($skipDepth >= 0 && $depth === 0) {
                    $skipDepth = -1;
                    continue;
                }
            }
            if ($skipDepth < 0) {
                $plain .= $char;
            }
        }
        if (preg_match_all('/([^{}]+)\{([^{}]*)\}/', $plain, $blocks, PREG_SET_ORDER)) {
            foreach ($blocks as $block) {
                $selectors = array_map('trim', explode(',', $block[1]));
                if (!in_array(':root', $selectors, true)) {
                    continue;
                }
                if (preg_match_all('/(--[a-z0-9_-]+)\s*:\s*([^;]+)/i', $block[2], $declarations, PREG_SET_ORDER)) {
                    foreach ($declarations as $declaration) {
                        $vars[strtolower($declaration[1])] = trim($declaration[2]);
                    }
                }
            }
        }

        return $vars;
    }

    /**
     * ตัวแปร :root ที่หน้าเว็บใช้ก่อนถึง stylesheet ของธีม
     * (Now.js core แล้วตามด้วย themes/gcms.css ตามลำดับที่ index.html โหลด)
     *
     * @return array
     */
    public static function coreVariables()
    {
        $vars = [];
        foreach (['Now/css/variables.css', 'themes/gcms.css'] as $file) {
            if (is_file(ROOT_PATH.$file)) {
                $vars = array_merge($vars, self::rootVariables(file_get_contents(ROOT_PATH.$file)));
            }
        }

        return $vars;
    }

    /**
     * ค่าสีของตัวแปร (ตาม var() ต่อไปจนได้ค่าจริง)
     *
     * @param string $name
     * @param array  $vars
     *
     * @return array|null [r, g, b, a] หรือ null ถ้าไม่ใช่สีเดี่ยว
     */
    public static function resolveColor($name, array $vars)
    {
        if (preg_match('/^#[0-9a-f]+$/i', $name)) {
            return self::parse($name);
        }
        $value = self::resolve($name, $vars);

        return $value === null ? null : self::parse($value);
    }

    /**
     * ค่าของตัวแปร โดยแทน var() ทั้งหมดด้วยค่าจริง
     *
     * @param string $name
     * @param array  $vars
     * @param int    $depth
     *
     * @return string|null
     */
    public static function resolve($name, array $vars, $depth = 0)
    {
        $name = strtolower($name);
        if ($depth > 12 || !isset($vars[$name])) {
            return null;
        }

        return self::resolveValue($vars[$name], $vars, $depth + 1);
    }

    /**
     * @param string $value
     * @param array  $vars
     * @param int    $depth
     *
     * @return string|null
     */
    private static function resolveValue($value, array $vars, $depth)
    {
        $value = trim((string) $value);
        if ($depth > 12) {
            return null;
        }
        if (preg_match('/^var\(\s*(--[a-z0-9_-]+)\s*(?:,\s*(.+))?\)$/i', $value, $match)) {
            $resolved = self::resolve($match[1], $vars, $depth);
            if ($resolved === null && isset($match[2])) {
                $resolved = self::resolveValue($match[2], $vars, $depth + 1);
            }

            return $resolved;
        }
        if (stripos($value, 'var(') !== false) {
            $value = preg_replace_callback('/var\(\s*(--[a-z0-9_-]+)\s*(?:,\s*([^()]*))?\)/i', function ($match) use ($vars, $depth) {
                $resolved = self::resolve($match[1], $vars, $depth);

                return $resolved === null ? (isset($match[2]) ? $match[2] : '') : $resolved;
            }, $value);
        }

        return $value;
    }

    /**
     * แปลงค่าสี CSS เป็น [r, g, b, a] (r,g,b = 0-255, a = 0-1)
     *
     * @param string $value
     *
     * @return array|null
     */
    public static function parse($value)
    {
        $value = strtolower(trim((string) $value));
        if ($value === 'transparent') {
            return [0, 0, 0, 0.0];
        }
        if ($value === 'white') {
            return [255, 255, 255, 1.0];
        }
        if ($value === 'black') {
            return [0, 0, 0, 1.0];
        }
        if (preg_match('/^#([0-9a-f]{3,8})$/', $value, $match)) {
            $hex = $match[1];
            if (strlen($hex) === 3 || strlen($hex) === 4) {
                $hex = preg_replace('/(.)/', '$1$1', $hex);
            }
            if (strlen($hex) !== 6 && strlen($hex) !== 8) {
                return null;
            }

            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
                strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) / 255 : 1.0
            ];
        }
        if (preg_match('/^(rgba?|hsla?)\(([^()]+)\)$/', $value, $match)) {
            $parts = preg_split('/\s*[,\/]\s*|\s+/', trim($match[2]));
            if (count($parts) < 3) {
                return null;
            }
            $alpha = 1.0;
            if (isset($parts[3])) {
                $alpha = substr($parts[3], -1) === '%' ? (float) $parts[3] / 100 : (float) $parts[3];
            }
            $alpha = max(0.0, min(1.0, $alpha));
            if ($match[1][0] === 'r') {
                $rgb = [];
                for ($i = 0; $i < 3; ++$i) {
                    $channel = substr($parts[$i], -1) === '%' ? (float) $parts[$i] * 2.55 : (float) $parts[$i];
                    $rgb[] = (int) round(max(0, min(255, $channel)));
                }
            } else {
                $rgb = self::hslToRgb((float) $parts[0], (float) $parts[1] / 100, (float) $parts[2] / 100);
            }
            $rgb[] = $alpha;

            return $rgb;
        }

        return null;
    }

    /**
     * #rrggbb ของสี
     *
     * @param array $rgb
     *
     * @return string
     */
    public static function toHex(array $rgb)
    {
        return sprintf('#%02x%02x%02x', (int) round($rgb[0]), (int) round($rgb[1]), (int) round($rgb[2]));
    }

    /**
     * อัตราส่วน contrast ระหว่างสองสี (1-21)
     *
     * @param array $a
     * @param array $b
     *
     * @return float
     */
    public static function contrast(array $a, array $b)
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Relative luminance ตาม WCAG 2.x
     *
     * @param array $rgb
     *
     * @return float
     */
    public static function luminance(array $rgb)
    {
        $channels = [];
        for ($i = 0; $i < 3; ++$i) {
            $c = $rgb[$i] / 255;
            $channels[] = $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * สีที่โปร่งแสงวางบนพื้นหลัง (ผลลัพธ์ทึบ)
     *
     * @param array $color
     * @param array $background
     *
     * @return array
     */
    public static function flatten(array $color, array $background)
    {
        $alpha = isset($color[3]) ? (float) $color[3] : 1.0;
        if ($alpha >= 1) {
            return [$color[0], $color[1], $color[2], 1.0];
        }
        $result = [];
        for ($i = 0; $i < 3; ++$i) {
            $result[] = $color[$i] * $alpha + $background[$i] * (1 - $alpha);
        }
        $result[] = 1.0;

        return $result;
    }

    /**
     * ปรับความสว่างของสีตัวอักษร (คง hue เดิม) จนได้ contrast ตามที่กำหนด
     *
     * @param array $foreground
     * @param array $background
     * @param float $minimum
     *
     * @return array
     */
    public static function fixForeground(array $foreground, array $background, $minimum)
    {
        // เผื่อไว้เล็กน้อย ค่าที่ปัดเศษแล้วจะได้ไม่ตกเกณฑ์
        $target = $minimum + 0.05;
        $white = [255, 255, 255, 1.0];
        $black = [0, 0, 0, 1.0];
        $lighter = self::contrast($white, $background) >= self::contrast($black, $background);
        list($h, $s, $l) = self::rgbToHsl($foreground);
        for ($step = 0; $step <= 1; $step += 0.02) {
            $lightness = $lighter ? min(1, $l + $step) : max(0, $l - $step);
            $rgb = self::hslToRgb($h, $s, $lightness);
            $rgb[] = 1.0;
            if (self::contrast($rgb, $background) >= $target) {
                return $rgb;
            }
        }

        return $lighter ? $white : $black;
    }

    /**
     * สีหลักจากโลโก้ของเว็บไซต์ (ไม่นับสีเทา ขาว ดำ และส่วนโปร่งใส)
     *
     * @param int $max จำนวนสีสูงสุด
     *
     * @return array รายการ #rrggbb เรียงจากสีที่ใช้มากที่สุด
     */
    public static function logoPalette($max = 3)
    {
        $file = self::logoFile();
        if ($file === null || !function_exists('imagecreatefromstring')) {
            return [];
        }
        $image = @imagecreatefromstring((string) file_get_contents($file));
        if ($image === false) {
            return [];
        }
        $size = 48;
        $small = imagecreatetruecolor($size, $size);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagecopyresampled($small, $image, 0, 0, 0, 0, $size, $size, imagesx($image), imagesy($image));

        $buckets = [];
        $total = 0;
        for ($y = 0; $y < $size; ++$y) {
            for ($x = 0; $x < $size; ++$x) {
                $pixel = imagecolorat($small, $x, $y);
                // alpha ของ GD: 0 = ทึบ, 127 = โปร่งใส
                if ((($pixel >> 24) & 0x7F) > 90) {
                    continue;
                }
                $rgb = [($pixel >> 16) & 0xFF, ($pixel >> 8) & 0xFF, $pixel & 0xFF];
                list(, $s, $l) = self::rgbToHsl($rgb);
                if ($s < 0.2 || $l < 0.1 || $l > 0.93) {
                    continue;
                }
                ++$total;
                $key = (($rgb[0] >> 5) << 6) | (($rgb[1] >> 5) << 3) | ($rgb[2] >> 5);
                if (!isset($buckets[$key])) {
                    $buckets[$key] = [0, 0, 0, 0];
                }
                $buckets[$key][0] += $rgb[0];
                $buckets[$key][1] += $rgb[1];
                $buckets[$key][2] += $rgb[2];
                ++$buckets[$key][3];
            }
        }
        // สีที่มีน้อยกว่า 3% ของภาพเป็นแค่ขอบภาพ ไม่ใช่สีของแบรนด์
        if ($total < $size * $size * 0.03) {
            return [];
        }
        usort($buckets, function ($a, $b) {
            return $b[3] <=> $a[3];
        });
        $result = [];
        foreach ($buckets as $bucket) {
            if ($bucket[3] < $total * 0.05) {
                break;
            }
            $rgb = [$bucket[0] / $bucket[3], $bucket[1] / $bucket[3], $bucket[2] / $bucket[3]];
            $distinct = true;
            foreach ($result as $picked) {
                if (self::distance($rgb, $picked) < 60) {
                    $distinct = false;
                    break;
                }
            }
            if ($distinct) {
                $result[] = $rgb;
                if (count($result) >= $max) {
                    break;
                }
            }
        }

        return array_map([__CLASS__, 'toHex'], $result);
    }

    /**
     * ไฟล์โลโก้ที่หน้าเว็บใช้ — ลำดับเดียวกับ Index\Index\Controller::setupSiteInfo()
     *
     * @return string|null
     */
    public static function logoFile()
    {
        $files = [];
        if (!empty(self::$cfg->header['logo'])) {
            $files[] = ROOT_PATH.DATA_FOLDER.'image/'.basename(self::$cfg->header['logo']);
        }
        foreach (['images/company_logo', 'images/site_logo', 'image/company_logo', 'image/site_logo'] as $name) {
            $file = \Gcms\Api::storedImage($name);
            if ($file !== null) {
                $files[] = ROOT_PATH.DATA_FOLDER.$file;
            }
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * ระยะห่างของสองสี (คร่าว ๆ ตามการมองเห็น)
     *
     * @param array $a
     * @param array $b
     *
     * @return float
     */
    private static function distance(array $a, array $b)
    {
        $r = ($a[0] + $b[0]) / 2;
        $dr = $a[0] - $b[0];
        $dg = $a[1] - $b[1];
        $db = $a[2] - $b[2];

        return sqrt((2 + $r / 256) * $dr * $dr + 4 * $dg * $dg + (2 + (255 - $r) / 256) * $db * $db);
    }

    /**
     * @param array $rgb
     *
     * @return array [h (0-360), s (0-1), l (0-1)]
     */
    public static function rgbToHsl(array $rgb)
    {
        $r = $rgb[0] / 255;
        $g = $rgb[1] / 255;
        $b = $rgb[2] / 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        if ($max == $min) {
            return [0.0, 0.0, $l];
        }
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        if ($max == $r) {
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        } elseif ($max == $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }

        return [$h * 60, $s, $l];
    }

    /**
     * @param float $h 0-360
     * @param float $s 0-1
     * @param float $l 0-1
     *
     * @return array [r, g, b]
     */
    public static function hslToRgb($h, $s, $l)
    {
        $h = fmod(fmod($h, 360) + 360, 360) / 360;
        $s = max(0, min(1, $s));
        $l = max(0, min(1, $l));
        if ($s == 0) {
            $v = (int) round($l * 255);

            return [$v, $v, $v];
        }
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $result = [];
        foreach ([$h + 1 / 3, $h, $h - 1 / 3] as $t) {
            if ($t < 0) {
                $t += 1;
            } elseif ($t > 1) {
                $t -= 1;
            }
            if ($t < 1 / 6) {
                $v = $p + ($q - $p) * 6 * $t;
            } elseif ($t < 1 / 2) {
                $v = $q;
            } elseif ($t < 2 / 3) {
                $v = $p + ($q - $p) * (2 / 3 - $t) * 6;
            } else {
                $v = $p;
            }
            $result[] = (int) round($v * 255);
        }

        return $result;
    }
}
