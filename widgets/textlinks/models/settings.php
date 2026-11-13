<?php
/**
 * @filesource widgets/textlinks/models/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlinks\Models;

/**
 * Textlinks Widget — Settings Model
 *
 * อ่าน/บันทึกลิงค์ทีละรายการ สำหรับหน้าแก้ไขในระบบจัดการ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\Model
{
    /**
     * Insert or update a textlink row.
     *
     * @param \Kotchasan\DB $db     Active DB instance
     * @param array         $data   Column → value map; must include 'id'
     * @param bool          $isNew  true = INSERT, false = UPDATE
     *
     * @return void
     */
    public static function save(\Kotchasan\DB $db, array $data, bool $isNew): void
    {
        if ($isNew) {
            $db->insert('textlink', $data);
        } else {
            $id = $data['id'];
            unset($data['id']);
            $db->update('textlink', ['id', $id], $data);
        }
    }

    /**
     * อ่านลิงค์ 1 รายการ, $id = 0 คืนค่าเริ่มต้นของรายการใหม่
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        if ($id > 0) {
            return static::createQuery()
                ->select()
                ->from('textlink')
                ->where(['id', $id])
                ->first();
        }

        return (object) [
            'id' => 0,
            'name' => '',
            'description' => '',
            'type' => 'text',
            'text' => '',
            'url' => '',
            'target' => '',
            'logo' => '',
            'publish_start' => null,
            'publish_end' => null
        ];
    }

    /**
     * get textlink from logo
     *
     * @param string $logo
     *
     * @return object|null
     */
    public static function formLogo($logo)
    {
        if (empty($logo)) {
            return null;
        }
        return static::createQuery()
            ->select()
            ->from('textlink')
            ->where(['logo', $logo])
            ->first();
    }
}
