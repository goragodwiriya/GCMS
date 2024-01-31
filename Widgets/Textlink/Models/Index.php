<?php
/**
 * @filesource Widgets/Textlink/Controllers/Settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlink\Models;

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
        $model = new static;
        $query = $model->db()->createQuery()
            ->select('name', 'type')
            ->from('textlink')
            ->groupBy('name', 'type')
            ->toArray();
        $result = array('' => '{LNG_all items}');
        foreach ($query->execute() as $item) {
            $result[$item['name']] = $item['name'].' ('.$item['type'].')';
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
            $mmktime = time();
            return (object) array(
                'id' => 0,
                'name' => $name,
                'type' => '',
                'description' => '',
                'text' => '',
                'url' => '',
                'target' => '',
                'logo' => '',
                'publish_start' => $mmktime,
                'publish_end' => $mmktime
            );
        } else {
            $model = new static;
            return $model->db()->createQuery()
                ->from('textlink')
                ->where($id)
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
        $model = new static;
        $q2 = $model->groupOr(array('publish_end', 0), array('publish_end', '>', mktime(0, 0, 0, $m, $d, $y)));
        $query = $model->db()->createQuery()
            ->select('id', 'name', 'text', 'type', 'url', 'target', 'logo', 'description', 'template', 'last_preview')
            ->from('textlink')
            ->where(array(
                array('published', 1),
                array('publish_start', '<', mktime(23, 59, 59, $m, $d, $y)),
                $q2
            ))
            ->order('link_order')
            ->cacheOn()
            ->toArray();
        $result = array();
        foreach ($query->execute() as $item) {
            $result[$item['name']][] = $item;
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
        $model = new static;
        $model->db()->createQuery()
            ->update('textlink')
            ->set(array('last_preview' => time()))
            ->where($id)
            ->execute();
    }
}
