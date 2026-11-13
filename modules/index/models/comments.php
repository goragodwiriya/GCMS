<?php
/**
 * @filesource modules/index/models/comments.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Comments;

/**
 * Shared helper to keep a parent record's cached comment summary
 * (comments count, comment_id, commentator_id, comment_date) in sync with
 * its child reply/comment table. Used by any module that caches this
 * summary on the parent row (board_q + board_r, index + comment, ...).
 * Call after inserting or deleting a reply/comment.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Recalculate comments/comment_id/commentator_id/comment_date on the
     * parent table from the current rows in the child (reply/comment) table.
     *
     * @param string $parentTable Parent table name (e.g. "board_q", "index")
     * @param string $childTable  Child table name (e.g. "board_r", "comment")
     * @param int    $parentId    Parent record ID
     * @param int    $moduleId    Module ID (both tables are scoped by module_id)
     *
     * @return void
     */
    public static function refreshSummary($parentTable, $childTable, $parentId, $moduleId)
    {
        $db = \Kotchasan\DB::create();

        $count = $db->count($childTable, [['index_id', $parentId], ['module_id', $moduleId]]);

        $lastRows = $db->select(
            $childTable,
            [['index_id', $parentId], ['module_id', $moduleId]],
            ['orderBy' => ['id' => 'DESC'], 'limit' => 1],
            ['id', 'member_id', 'updated_at']
        );
        $last = $lastRows[0] ?? null;

        $db->update($parentTable, ['id', $parentId], [
            'comments' => $count,
            'comment_id' => $last ? (int) $last->id : 0,
            'commentator_id' => $last ? (int) $last->member_id : 0,
            'comment_date' => $last ? $last->updated_at : null,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
