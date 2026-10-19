<?php
/**
 * @filesource Gcms/Theme.php
 *
 * Theme location resolver — ธีมของเว็บอยู่ที่ themes/{slug}/ ตำแหน่งเดียวกับที่
 * Kotchasan\Template::init() ใช้แสดงผลหน้าเว็บ
 *
 * GCMS 15 เป็นเว็บเดี่ยว ไม่มีธีมส่วนตัวแบบระบบหลายเว็บ หน้าเลือกธีมของแอดมิน
 * แสดงและเปิดใช้ได้เฉพาะธีมใน themes/
 *
 * ข้อยกเว้นเดียวคือธีมตัวอย่างของ AI Theme Generator (ai-preview-<user id>) ซึ่งอยู่ใน
 * DATA_FOLDER.themes/ — Kotchasan\Template::init() หาธีมใน DATA_FOLDER.themes/ ก่อน
 * themes/ จึงแสดงผลได้ แต่ไม่ปรากฏในหน้าเลือกธีมและเปิดใช้เป็นธีมของเว็บไม่ได้
 * (หน้าเว็บแสดงเฉพาะแอดมินที่เปิดด้วย ?theme_preview=)
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

    /**
     * ชื่อธีมตัวอย่างของ AI Theme Generator ของผู้ใช้คนนี้
     *
     * @param int $userId
     *
     * @return string
     */
    public static function previewSlug($userId)
    {
        return 'ai-preview-'.(int) $userId;
    }

    /**
     * เป็นชื่อธีมตัวอย่างของ AI Theme Generator หรือไม่
     *
     * @param string $slug
     *
     * @return bool
     */
    public static function isPreview($slug)
    {
        return (bool) preg_match('/^ai-preview(?:-[0-9]+)?$/', (string) $slug);
    }

    /**
     * โฟลเดอร์ของธีมตัวอย่าง (DATA_FOLDER.themes/ai-preview-<id>/) หรือ null ถ้าชื่อไม่ถูกต้อง
     *
     * @param string $slug
     *
     * @return string|null
     */
    public static function previewDir($slug)
    {
        return self::isPreview($slug) ? ROOT_PATH.DATA_FOLDER.'themes/'.$slug.'/' : null;
    }

    /**
     * ธีมที่แอดมินขอดูตัวอย่างด้วย ?theme_preview=<slug>
     * ได้ทั้งธีมใน themes/ (ก่อนเปิดใช้) และธีมตัวอย่างของ AI Theme Generator
     *
     * @param string $slug
     *
     * @return string|null ชื่อธีมที่แสดงได้ หรือ null
     */
    public static function previewable($slug)
    {
        $slug = strtolower(trim((string) $slug));
        if ($slug === '' || !self::isValidSlug($slug)) {
            return null;
        }
        $dir = self::previewDir($slug);
        if ($dir !== null) {
            return is_file($dir.'index.html') ? $slug : null;
        }

        return self::exists($slug) ? $slug : null;
    }
}
