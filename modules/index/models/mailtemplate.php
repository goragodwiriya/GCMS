<?php
/**
 * @filesource modules/index/models/mailtemplate.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Mailtemplate;

/**
 * Class for loading mail template items from the GCMS database
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get mail template by ID (สำหรับหน้าแก้ไข)
     *
     * @param int $id
     *
     * @return object|null Return mail template object, null if not found
     */
    public static function get($id)
    {
        if (empty($id)) {
            return null;
        }
        // ไม่ cacheOn — แก้แล้วเปิดใหม่ต้องเห็นค่าล่าสุด (การ update ไม่ล้างแคชคิวรี)
        $template = static::createQuery()
            ->select()
            ->from('emailtemplate')
            ->where(['id', (int) $id])
            ->first();
        if (!$template) {
            return null;
        }
        $template->copy_to = empty($template->copy_to) ? [] : explode(',', $template->copy_to);
        // ตอนบันทึก Text::detail() เข้ารหัส { } \ และเก็บที่อยู่เว็บเป็น {WEBURL}
        $template->detail = str_replace(
            ['&#x007B;', '&#x007D;', '&#92;', '{WEBURL}'],
            ['{', '}', '\\', WEB_URL],
            $template->detail
        );
        $template->variables = implode(' ', \Gcms\EmailTemplate::variables((string) $template->code));

        return $template;
    }

    /**
     * มีแม่แบบ code นี้ในภาษานี้อยู่แล้วหรือไม่ (ไม่นับแถว $exceptId)
     * หนึ่ง code มีได้ภาษาละหนึ่งแถว — Gcms\EmailTemplate เลือกแม่แบบจาก code + language
     *
     * @param object $template
     * @param string $language
     * @param int    $exceptId
     *
     * @return bool
     */
    public static function exists($template, $language, $exceptId = 0)
    {
        return (bool) static::createQuery()
            ->select('id')
            ->from('emailtemplate')
            ->where([
                ['code', $template->code],
                ['language', $language],
                ['id', '!=', (int) $exceptId]
            ])
            ->first();
    }
}
