<?php
/**
 * @filesource modules/board/models/stories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\Stories;

/**
 * Board Lists Model — frontend pagination + widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * @var mixed
     */
    private $instance = null;

    /**
     * Create a new instance with params
     *
     * @param object $index
     *
     * @return static
     */
    public static function create($index)
    {
        $obj = new static();

        $where = [
            ['Q.module_id', $index->module_id],
            ['Q.published', 1]
        ];
        if (!empty($index->category_id)) {
            $where[] = ['Q.category_id', $index->category_id];
        }

        $query = \Kotchasan\Model::createQuery()
            ->from('board_q Q')
            ->where($where)
            ->cacheOn();

        $obj->instance = $query;

        return $obj;
    }

    /**
     * Count total
     *
     * @return int
     */
    public function count(): int
    {
        $query = clone $this->instance;
        $row = $query->selectCount()->first();
        return $row ? (int) $row->count : 0;
    }

    /**
     * Paginate topics (pinned first, then by updated_at DESC)
     *
     * @param int $page
     * @param int $limit
     *
     * @return array Pagination info + items
     */
    public function paginate($page, $limit)
    {
        $total = $this->count();
        $total_pages = $limit > 0 ? (int) ceil($total / $limit) : 1;
        $page = max(1, min($page, max(1, $total_pages)));
        $offset = ($page - 1) * $limit;

        $query = clone $this->instance;
        $items = $query
            ->select(
                'Q.id',
                'Q.topic',
                'Q.category_id',
                'U.name AS sender',
                'U.status',
                'Q.member_id',
                'Q.created_at',
                'Q.updated_at',
                'Q.visited',
                'Q.comments',
                'Q.pin',
                'Q.locked',
                'Q.comment_date',
                'A.name commentator',
                'A.status reply_status'
            )
            ->join('user U', ['U.id', 'Q.member_id'], 'LEFT')
            ->join('user A', ['A.id', 'Q.commentator_id'], 'LEFT')
            ->orderBy('Q.pin', 'DESC')
            ->orderBy('Q.updated_at', 'DESC')
            ->limit($limit, $offset)
            ->fetchAll();

        return [
            'page' => $page,
            'total_pages' => $total_pages,
            'total' => $total,
            'items' => $items
        ];
    }
}
