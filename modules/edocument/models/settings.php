<?php
/**
 * @filesource modules/edocument/models/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Settings;

/**
 * E-Document module settings helper
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Default settings for a new e-document module.
     * Keys match the config stored by the legacy GCMS edocument module,
     * so existing `modules`.`config` rows are read back unchanged.
     *
     * @return array
     */
    public static function defaultSettings()
    {
        return [
            'file_typies' => ['doc', 'ppt', 'pptx', 'docx', 'rar', 'zip', 'jpg', 'pdf'],
            'upload_size' => 2097152,
            'format_no' => 'E-%04d',
            'list_per_page' => 20,
            'send_mail' => 1,
            'download_action' => 0,
            'moderator' => [1],
            'can_upload' => [1],
            'can_config' => [1]
        ];
    }

    /**
     * Normalize module config with defaults.
     *
     * @param object|array|null $config
     *
     * @return object
     */
    public static function normalizeConfig($config)
    {
        $defaults = (object) self::defaultSettings();
        if (is_array($config)) {
            $config = (object) $config;
        } elseif (!is_object($config)) {
            $config = (object) [];
        }

        foreach ($defaults as $key => $value) {
            if (!isset($config->$key)) {
                $config->$key = $value;
            }
        }

        $config->file_typies = self::sanitizeFileTypes($config->file_typies);
        $config->upload_size = max(1024, (int) $config->upload_size);
        $config->format_no = trim((string) $config->format_no);
        $config->list_per_page = min(100, max(1, (int) $config->list_per_page));
        $config->send_mail = empty($config->send_mail) ? 0 : 1;
        $config->download_action = empty($config->download_action) ? 0 : 1;

        $config->can_upload = self::toStatusArray($config->can_upload, [1]);
        $config->moderator = self::toStatusArray($config->moderator, [1]);
        $config->can_config = self::toStatusArray($config->can_config, [1]);

        return $config;
    }

    /**
     * Convert config statuses to a unique integer array.
     *
     * @param mixed $statuses
     * @param array $fallback
     *
     * @return array
     */
    private static function toStatusArray($statuses, array $fallback)
    {
        if (!is_array($statuses)) {
            return $fallback;
        }

        $result = [];
        foreach ($statuses as $status) {
            $status = (int) $status;
            if (!in_array($status, $result, true)) {
                $result[] = $status;
            }
        }

        return empty($result) ? $fallback : $result;
    }

    /**
     * Normalize allowed file extensions.
     * `edocument`.`ext` is varchar(4), so longer extensions cannot be stored.
     *
     * @param mixed $typies
     *
     * @return array
     */
    private static function sanitizeFileTypes($typies)
    {
        if (!is_array($typies)) {
            return self::defaultSettings()['file_typies'];
        }

        $result = [];
        foreach ($typies as $typ) {
            $typ = strtolower(trim((string) $typ));
            if ($typ !== '' && preg_match('/^[a-z0-9]{2,4}$/', $typ) && !isset($result[$typ])) {
                $result[$typ] = $typ;
            }
        }

        return empty($result) ? self::defaultSettings()['file_typies'] : array_values($result);
    }
}
