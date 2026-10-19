<?php
/**
 * @filesource modules/board/models/boards.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Setup;

/**
 * Boards List Model — DataTable queries + bulk operations
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Build the DataTable query for topics
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [
            ['Q.module_id', $params['module_id']]
        ];
        if (!empty($params['category_id'])) {
            $where[] = ['Q.category_id', $params['category_id']];
        }
        if ($params['published'] !== '') {
            $where[] = ['Q.published', $params['published']];
        }
        if ($params['pin'] !== '') {
            $where[] = ['Q.pin', $params['pin']];
        }
        if ($params['locked'] !== '') {
            $where[] = ['Q.locked', $params['locked']];
        }
        $query = static::createQuery()
            ->select(
                'Q.id',
                'Q.module_id',
                'M.module',
                'Q.topic',
                'Q.category_id',
                'Q.published',
                'Q.pin',
                'Q.locked',
                'U.name sender',
                'Q.created_at',
                'Q.updated_at',
                'Q.visited',
                'Q.comments',
                'Q.comment_date'
            )
            ->from('board_q Q')
            ->join('modules M', ['M.id', 'Q.module_id'])
            ->join('user U', ['U.id', 'Q.member_id'], 'LEFT')
            ->where($where);

        // Search
        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['Q.topic', 'LIKE', $search],
                ['Q.sender', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Delete topics and all their replies
     *
     * @param array $ids
     *
     * @return int Number deleted
     */
    public static function remove($ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $db = \Kotchasan\DB::create();
        $removed = 0;

        foreach ($ids as $id) {
            $id = (int) $id;
            // Delete all replies first
            $db->delete('board_r', ['index_id', $id], 0);
            // Delete topic
            $result = $db->delete('board_q', ['id', $id], 1);
            if ($result > 0) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Update a status column for multiple topics
     *
     * @param array  $ids
     * @param string $column
     * @param int    $value
     *
     * @return bool
     */
    public static function updateStatus($ids, $column, $value)
    {
        if (empty($ids)) {
            return false;
        }

        $db = \Kotchasan\DB::create();

        foreach ($ids as $id) {
            $db->update('board_q', ['id', (int) $id], [$column => (int) $value]);
        }

        return true;
    }
}
