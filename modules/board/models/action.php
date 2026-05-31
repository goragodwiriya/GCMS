<?php
/**
 * @filesource modules/board/models/action.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Action;

/**
 * Board Model - Frontend actions (delete, pin, lock)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * Delete a reply
     *
     * @param int $reply_id
     * @param int $module_id
     *
     * @return int Number of affected rows
     */
    public static function deleteReply($reply_id, $module_id)
    {
        return \Kotchasan\DB::create()->delete('board_r', [['id', $reply_id], ['module_id', $module_id]]);
    }

    /**
     * Delete a topic and all its replies
     *
     * @param int $id
     * @param int $module_id
     *
     * @return int Number of affected rows
     */
    public static function deleteTopic($id, $module_id)
    {
        $db = \Kotchasan\DB::create();
        $rowDelete = $db->delete('board_q', [['id', $id], ['module_id', $module_id]]);
        $db->delete('board_r', [['index_id', $id], ['module_id', $module_id]], 0);

        return $rowDelete;
    }

    /**
     * Toggle pin status of a topic
     *
     * @param int $id
     * @param int $module_id
     * @param string $action
     *
     * @return int Number of affected rows
     */
    public static function togglePin($id, $module_id, $action)
    {
        return \Kotchasan\DB::create()->update('board_q', [['id', $id], ['module_id', $module_id]], ['pin' => ($action === 'pin' ? 1 : 0)]);
    }

    /**
     * Toggle lock status of a topic
     *
     * @param int $id
     * @param int $module_id
     * @param string $action
     *
     * @return int Number of affected rows
     */
    public static function toggleLock($id, $module_id, $action)
    {
        return \Kotchasan\DB::create()->update('board_q', [['id', $id], ['module_id', $module_id]], ['locked' => ($action === 'lock' ? 1 : 0)]);
    }
}
