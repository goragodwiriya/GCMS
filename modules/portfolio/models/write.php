<?php
/**
 * @filesource modules/portfolio/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Portfolio\Write;

/**
 * Portfolio admin edit-form model — mirrors Personnel\Write\Model's shape.
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
                'title' => '',
                'keywords' => '',
                'detail' => '',
                'url' => '',
                'published' => 1,
                'image' => [
                    ['url' => WEB_URL.'images/no-image.webp', 'name' => 'Choose file']
                ]
            ];
        }

        $item = static::createQuery()
            ->select()
            ->from('portfolio')
            ->where(['id', $id])
            ->first();

        if (!$item) {
            return false;
        }

        if ($item->image !== '' && file_exists(ROOT_PATH.DATA_FOLDER.'portfolio/'.$item->image)) {
            $item->image = [
                ['url' => WEB_URL.DATA_FOLDER.'portfolio/'.$item->image, 'name' => $item->image]
            ];
        } else {
            $item->image = [
                ['url' => WEB_URL.'images/no-image.webp', 'name' => 'Choose file']
            ];
        }

        return $item;
    }
}
