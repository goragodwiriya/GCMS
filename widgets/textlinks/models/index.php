<?php
/**
 * @filesource widgets/textlink/models/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlinks\Models;

/**
 * Controller สำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * อ่านรายชื่อ type และ "ทุกรายการ"
     * สำหรับใส่ใน select
     *
     * @return array
     */
    public static function getTypies()
    {
        $query = static::createQuery()
            ->select('name', 'type')
            ->from('textlink')
            ->groupBy('name')
            ->groupBy('type');
        $result = ['' => '{LNG_all items}'];
        foreach ($query->fetchAll() as $item) {
            $result[$item->name] = $item->name.' ('.$item->type.')';
        }
        return $result;
    }

    /**
     * อ่าน textlink จาก Id
     *
     * @param int    $id   Id ของ Textlink, หมายถึงรายการใหม่
     * @param string $name ชื่อที่เลือกสำหรับรายการใหม่
     *
     * @return object
     */
    public static function getById($id, $name)
    {
        if ($id == 0) {
            $today = date('Y-m-d');
            return (object) [
                'id' => 0,
                'name' => $name,
                'type' => '',
                'description' => '',
                'text' => '',
                'url' => '',
                'target' => '',
                'logo' => '',
                'publish_start' => $today,
                'publish_end' => $today
            ];
        } else {
            return static::createQuery()
                ->from('textlink')
                ->where(['id', $id])
                ->first();
        }
    }

    /**
     * query ข้อมูลแบนเนอร์ทั้งหมด
     *
     * @param int $m เดือนนี้
     * @param int $d วันนี้
     * @param int $y ปีนี้
     *
     * @return array
     */
    public static function get($m, $d, $y)
    {
        $query = static::createQuery()
            ->select('id', 'name', 'text', 'type', 'url', 'target', 'logo', 'description', 'template', 'last_preview')
            ->from('textlink')
            ->where([
                ['published', 1],
                ['publish_start', '<=', date('Y-m-d', mktime(23, 59, 59, $m, $d, $y))]
            ])
            ->where([
                ['publish_end', null],
                ['publish_end', '>=', date('Y-m-d', mktime(0, 0, 0, $m, $d, $y))]
            ], 'OR')
            ->orderBy('link_order')
            ->cacheOn();
        $result = [];
        foreach ($query->fetchAll() as $item) {
            $result[$item->name][] = $item;
        }
        return $result;
    }

    /**
     * อัปเดตการเปิดดู
     *
     * @param int $id
     */
    public static function previewUpdate($id)
    {
        static::createQuery()
            ->update('textlink')
            ->set(['last_preview' => time()])
            ->where(['id', $id])
            ->execute();
    }
}
