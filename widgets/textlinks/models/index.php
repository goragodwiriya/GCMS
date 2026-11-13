<?php
/**
 * @filesource widgets/textlinks/models/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlinks\Models;

use Kotchasan\Database\Sql;

/**
 * Textlinks Widget — Frontend Model
 *
 * อ่านลิงค์ของแต่ละกลุ่ม (name) สำหรับแสดงผลหน้าเว็บ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * ชนิดของลิงค์ที่รองรับ (เมนูข้อความ และ เมนูรูปภาพ)
     */
    const TYPES = ['text', 'image'];

    /**
     * แปลงชนิดของข้อมูลเดิม (custom, menu, banner, hero, slideshow) ให้เหลือ 2 ชนิด
     *
     * @param string|null $type
     *
     * @return string text หรือ image
     */
    public static function normalizeType($type)
    {
        return in_array($type, ['image', 'banner', 'hero', 'slideshow'], true) ? 'image' : 'text';
    }

    /**
     * อ่านลิงค์ของกลุ่มที่ระบุ เฉพาะรายการที่เผยแพร่และอยู่ในช่วงเวลาที่กำหนด
     * เรียงตามลำดับที่จัดไว้ (link_order)
     *
     * @param string $name ชื่อกลุ่ม
     *
     * @return array รายการลิงค์ (แต่ละรายการมี property type ที่ normalize แล้ว)
     */
    public static function getItems($name)
    {
        if ($name === '') {
            return [];
        }

        $today = time();
        $query = static::createQuery()
            ->select('id', 'name', 'text', 'type', 'url', 'target', 'logo', 'description')
            ->from('textlink')
            ->where([
                ['published', 1],
                ['name', $name]
            ])
            ->where([
                ['publish_start', 0],
                ['publish_start', '<=', $today]
            ], 'OR')
            ->where([
                ['publish_end', 0],
                ['publish_end', '>=', $today]
            ], 'OR')
            ->orderBy('link_order')
            ->cacheOn();

        $result = [];
        foreach ($query->fetchAll() as $item) {
            $item->type = self::normalizeType($item->type);
            $result[] = $item;
        }

        return $result;
    }

    /**
     * รายชื่อกลุ่มทั้งหมดที่มีอยู่ สำหรับให้เลือกตอนติดตั้ง widget ลง section
     *
     * @return array รายการ ['value' => name, 'label' => name]
     */
    public static function getGroups()
    {
        $query = static::createQuery()
            ->select(Sql::DISTINCT('name'))
            ->from('textlink')
            ->orderBy('name');

        $result = [];
        foreach ($query->fetchAll() as $row) {
            if ($row->name !== '' && $row->name !== null) {
                $result[] = ['value' => $row->name, 'label' => $row->name];
            }
        }

        return $result;
    }
}
