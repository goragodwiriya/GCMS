<?php
/**
 * @filesource modules/event/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Event\Write;

/**
 * Event admin edit-form model — mirrors Portfolio\Write\Model's shape.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param int $id        0 = new item
     * @param int $module_id
     *
     * @return object|false
     */
    public static function get($id, $module_id)
    {
        if ($id === 0) {
            $now = time();
            return (object) [
                'id' => 0,
                'module_id' => $module_id,
                'topic' => '',
                'color' => '#4CAF50',
                'description' => '',
                'keywords' => '',
                'detail' => '',
                'begin_date' => date('Y-m-d', $now),
                'begin_time' => date('H:i', $now),
                'to_time' => date('H:i', $now + 3600),
                'forever' => 1,
                'published' => 1,
                'published_date' => date('Y-m-d', $now)
            ];
        }

        $item = static::createQuery()
            ->select()
            ->from('event')
            ->where([
                ['id', $id],
                ['module_id', $module_id]
            ])
            ->first();

        if (!$item) {
            return false;
        }

        // แยกวันและเวลาสำหรับฟอร์ม
        $begin = empty($item->begin_date) ? time() : strtotime($item->begin_date);
        $item->begin_date = date('Y-m-d', $begin);
        $item->begin_time = date('H:i', $begin);
        $item->forever = empty($item->end_date) || $item->end_date === '0000-00-00 00:00:00' ? 1 : 0;
        $item->to_time = $item->forever ? date('H:i', $begin + 3600) : date('H:i', strtotime($item->end_date));
        $item->published = (int) $item->published;
        if (empty($item->published_date) || $item->published_date === '0000-00-00') {
            $item->published_date = date('Y-m-d', $begin);
        }

        return $item;
    }
}
