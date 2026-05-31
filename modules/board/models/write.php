<?php
/**
 * @filesource modules/board/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Write;

/**
 * Board Topic Model — Admin form data
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get topic data for admin form
     *
     * @param int $id Topic ID (0 = new)
     *
     * @return object|null
     */
    public static function get($id)
    {
        if ($id === 0) {
            // Return default empty object
            return (object) [
                'id' => 0,
                'module_id' => 0,
                'category_id' => 0,
                'topic' => '',
                'detail' => '',
                'published' => 1,
                'pin' => 0,
                'locked' => 0,
                'can_reply' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'visited' => 0,
                'comments' => 0
            ];
        }

        $row = static::createQuery()
            ->select(
                'Q.id',
                'Q.module_id',
                'Q.category_id',
                'Q.topic',
                'Q.detail',
                'Q.published',
                'Q.pin',
                'Q.locked',
                'Q.member_id',
                'Q.created_at',
                'Q.visited',
                'Q.comments'
            )
            ->from('board_q Q')
            ->where(['Q.id', $id])
            ->first();

        if (!$row) {
            return null;
        }

        // Decode stored detail
        $row->detail = str_replace('{WEBURL}', WEB_URL, $row->detail);

        return $row;
    }
}
