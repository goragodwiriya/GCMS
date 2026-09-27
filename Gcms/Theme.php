<?php
/**
 * @filesource Gcms/Theme.php
 *
 * Theme location resolver — the single place that understands the two theme
 * locations used by the system, mirroring Kotchasan\Template::init():
 *
 *   personal theme : DATA_FOLDER.themes/{slug}/  (checked first)
 *   system theme   : themes/{slug}/
 *
 * DATA_FOLDER is already per-user in this deployment
 * (datas/users/{username}/), so a theme in DATA_FOLDER.themes/ is inherently
 * private to the site's owner — no ownership metadata or scanning needed. A
 * personal theme shadows a system theme of the same slug.
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
     * The site's personal-themes directory (trailing slash).
     *
     * @return string
     */
    public static function personalDir()
    {
        return ROOT_PATH.DATA_FOLDER.'themes/';
    }

    /**
     * Does this slug exist as a personal theme (in DATA_FOLDER.themes/)?
     *
     * @param string $slug
     *
     * @return bool
     */
    public static function isPersonal($slug)
    {
        return self::isValidSlug($slug) && is_dir(self::personalDir().$slug.'/');
    }

    /**
     * Absolute filesystem directory for a theme slug (trailing slash), or
     * null when the slug is invalid. Personal location wins when it exists,
     * matching Kotchasan\Template::init().
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
        if (self::isPersonal($slug)) {
            return self::personalDir().$slug.'/';
        }
        return ROOT_PATH.'themes/'.$slug.'/';
    }

    /**
     * Public URL base for a theme slug (trailing slash), or null when the
     * slug is invalid. Same personal-first rule as dir().
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
        if (self::isPersonal($slug)) {
            return WEB_URL.DATA_FOLDER.'themes/'.$slug.'/';
        }
        return WEB_URL.'themes/'.$slug.'/';
    }
}
