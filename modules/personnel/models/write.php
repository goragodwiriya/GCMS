<?php
/**
 * @filesource modules/personnel/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Personnel\Write;

/**
 * Personnel API Model (CRUD)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get personnel data for admin edit form
     *
     * @param int $id        0 = new person
     * @param int $module_id Module ID
     *
     * @return object|false
     */
    public static function get($id, $module_id)
    {
        if ($id === 0) {
            return (object) [
                'id' => 0,
                'module_id' => $module_id,
                'name' => '',
                'department' => '',
                'position' => '',
                'picture' => '',
                'phone' => '',
                'email' => '',
                'level' => 3,
                'published' => 1,
                'detail' => ''
            ];
        }

        $person = static::createQuery()
            ->select()
            ->from('personnel')
            ->where(['id', $id])
            ->first();

        if (!$person) {
            return false;
        }

        if (file_exists(ROOT_PATH.DATA_FOLDER.'personnel/'.$person->picture)) {
            $person->image = [
                [
                    'url' => WEB_URL.DATA_FOLDER.'personnel/'.$person->picture,
                    'name' => $person->picture
                ]
            ];
        } else {
            $person->image = [
                [
                    'url' => WEB_URL.'images/no-image.webp',
                    'name' => 'Choose file'
                ]
            ];
        }

        return $person;
    }
}
