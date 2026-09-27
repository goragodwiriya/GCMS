<?php
/**
 * @filesource modules/video/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Video\Write;

/**
 * Video admin edit-form model — mirrors Portfolio\Write\Model's shape.
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
            return (object) [
                'id' => 0,
                'module_id' => $module_id,
                'youtube' => '',
                'topic' => '',
                'description' => '',
                'thumbnail' => WEB_URL.'images/no-image.webp'
            ];
        }

        $item = static::createQuery()
            ->select()
            ->from('video')
            ->where([
                ['id', $id],
                ['module_id', $module_id]
            ])
            ->first();

        if (!$item) {
            return false;
        }

        if ($item->youtube !== '' && is_file(ROOT_PATH.DATA_FOLDER.'video/'.$item->youtube.'.jpg')) {
            $item->thumbnail = WEB_URL.DATA_FOLDER.'video/'.$item->youtube.'.jpg?v='.time();
        } else {
            $item->thumbnail = WEB_URL.'images/no-image.webp';
        }

        return $item;
    }
}
