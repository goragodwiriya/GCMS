<?php
/**
 * @filesource Web/Widget.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Web;

use Kotchasan\ArrayTool;

/**
 * คลาส Widget สำหรับจัดการข้อมูล View ใน GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Widget extends \Kotchasan\Model
{
    /**
     * ดึงข้อมูล widget ตาม owner ที่ระบุ
     *
     * @param array $params ข้อมูลพารามิเตอร์ที่ต้องการดึงข้อมูล โดยต้องมีคีย์ 'owner'
     *
     * @return mixed ข้อมูล widget หากพบ หรือ null หากไม่พบ
     */
    public static function get($params)
    {
        // ตรวจสอบว่ามีการระบุ 'owner' หรือไม่
        if (empty($params['owner'])) {
            return null;
        }

        // Query ข้อมูล widget จากฐานข้อมูล โดยใช้ owner ที่ระบุ
        $widget = static::createQuery()
            ->from('widget')
            ->where(['owner', $params['owner']])
            ->cacheOn()
            ->first();

        // ถ้าพบข้อมูล widget
        if ($widget) {
            // แปลงค่า config จากรูปแบบ serialize เป็น array
            $widget->config = ArrayTool::unserialize($widget->config);
        }

        // คืนค่าข้อมูล widget หรือ null
        return $widget;
    }
}
