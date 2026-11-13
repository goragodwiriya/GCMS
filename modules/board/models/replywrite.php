<?php
/**
 * @filesource modules/board/models/replywrite.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Replywrite;

/**
 * Board Reply Model — Edit form data
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get a reply row for the edit form.
     *
     * @param int $id Reply ID
     *
     * @return object|null
     */
    public static function get($id)
    {
        $row = static::createQuery()
            ->select('id', 'module_id', 'index_id', 'member_id', 'detail', 'picture')
            ->from('board_r')
            ->where(['id', $id])
            ->first();

        if (!$row) {
            return null;
        }

        // Decode stored detail
        $row->detail = str_replace('{WEBURL}', WEB_URL, $row->detail);

        return $row;
    }

    /**
     * Get the parent topic of a reply (for category/permission/breadcrumb context).
     *
     * @param int $id Topic ID (board_q.id)
     *
     * @return object|null
     */
    public static function getTopic($id)
    {
        return static::createQuery()
            ->select('id', 'module_id', 'category_id', 'topic')
            ->from('board_q')
            ->where(['id', $id])
            ->first();
    }
}
