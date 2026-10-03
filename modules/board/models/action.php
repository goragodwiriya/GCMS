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
     * Delete a reply: removes its attached image (if any) and refreshes
     * the parent topic's comments/comment_id/commentator_id/comment_date.
     *
     * @param int $reply_id
     * @param int $module_id
     *
     * @return int Number of affected rows
     */
    public static function deleteReply($reply_id, $module_id)
    {
        $db = \Kotchasan\DB::create();
        $reply = $db->first('board_r', [['id', $reply_id], ['module_id', $module_id]]);
        if (!$reply) {
            return 0;
        }

        // Remove the attached image, if any
        if (!empty($reply->picture)) {
            $path = ROOT_PATH.DATA_FOLDER.'board/'.$reply->picture;
            if (is_file($path)) {
                unlink($path);
            }
        }

        $rowDelete = $db->delete('board_r', [['id', $reply_id], ['module_id', $module_id]]);

        if ($rowDelete) {
            self::refreshTopicCommentSummary($reply->index_id, $module_id);
        }

        return $rowDelete;
    }

    /**
     * Delete a topic, all its replies, and every attached image (topic + replies).
     *
     * @param int $id
     * @param int $module_id
     *
     * @return int Number of affected rows
     */
    public static function deleteTopic($id, $module_id)
    {
        $db = \Kotchasan\DB::create();

        $topic = $db->first('board_q', [['id', $id], ['module_id', $module_id]]);
        if ($topic && !empty($topic->picture)) {
            $path = ROOT_PATH.DATA_FOLDER.'board/'.$topic->picture;
            if (is_file($path)) {
                unlink($path);
            }
        }

        $replies = $db->select('board_r', [['index_id', $id], ['module_id', $module_id]], [], ['picture']);
        foreach ($replies as $reply) {
            if (!empty($reply->picture)) {
                $path = ROOT_PATH.DATA_FOLDER.'board/'.$reply->picture;
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        $rowDelete = $db->delete('board_q', [['id', $id], ['module_id', $module_id]]);
        $db->delete('board_r', [['index_id', $id], ['module_id', $module_id]], 0);

        return $rowDelete;
    }

    /**
     * Recalculate a topic's comments/comment_id/commentator_id/comment_date
     * from the current board_r rows. Call after adding or removing a reply.
     *
     * @param int $topic_id
     * @param int $module_id
     *
     * @return void
     */
    public static function refreshTopicCommentSummary($topic_id, $module_id)
    {
        \Index\Comments\Model::refreshSummary('board_q', 'board_r', $topic_id, $module_id);
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
