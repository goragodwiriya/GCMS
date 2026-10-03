<?php
/**
 * @filesource Gcms/Theme.php
 *
 * Theme location resolver — ธีมของเว็บอยู่ที่ themes/{slug}/ ตำแหน่งเดียวกับที่
 * Kotchasan\Template::init() ใช้แสดงผลหน้าเว็บ
 *
 * GCMS 15 เป็นเว็บเดี่ยว ไม่มีธีมส่วนตัวใน DATA_FOLDER.themes/ แบบระบบหลายเว็บ
 * (Kotchasan รุ่นนี้ไม่อ่านธีมจาก DATA_FOLDER) หน้าเลือกธีมของแอดมินจึงต้องแสดง
 * และเปิดใช้ได้เฉพาะธีมใน themes/ ไม่อย่างนั้นจะเลือกธีมที่หน้าเว็บแสดงไม่ได้
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gcms;

/**
 * Theme location helper
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Theme
{
    /**
     * Is this a valid theme slug?
     *
     * @param string $slug
     *
     * @return bool
     */
    public static function isValidSlug($slug)
    {
        return (bool) preg_match('/^[a-z0-9-]+$/', (string) $slug);
    }

    /**
     * Absolute filesystem directory for a theme slug (trailing slash), or
     * null when the slug is invalid.
     *
     * @param string $slug
     *
     * @return string|null
     */
    public static function dir($slug)
    {
        if (!self::isValidSlug($slug)) {
            return null;
        }
        return ROOT_PATH.'themes/'.$slug.'/';
    }

    /**
     * Public URL base for a theme slug (trailing slash), or null when the
     * slug is invalid.
     *
     * @param string $slug
     *
     * @return string|null
     */
    public static function url($slug)
    {
        if (!self::isValidSlug($slug)) {
            return null;
        }
        return WEB_URL.'themes/'.$slug.'/';
    }

    /**
     * ธีมนี้ใช้ได้หรือไม่ (มีโฟลเดอร์และ index.html ที่ Template ใช้เป็นโครงหน้าเว็บ)
     *
     * @param string $slug
     *
     * @return bool
     */
    public static function exists($slug)
    {
        $dir = self::dir($slug);

        return $dir !== null && is_file($dir.'index.html');
    }
}
