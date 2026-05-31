<?php
/**
 * @filesource modules/document/models/stories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Stories;

/**
 * Document Lists Model
 *
 * Used by frontend pagination and widget
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
            ['I.module_id', $index->module_id],
            ['I.index', 0],
            ['I.published', 1],
            ['I.published_date', '<=', date('Y-m-d')],
            ['D.language', ['', LANGUAGE]]
        ];
        if (!empty($index->category_id)) {
            $where[] = ['I.category_id', $index->category_id];
        }

        $query = \Kotchasan\Model::createQuery()
            ->from('index I')
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
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
     * Paginate articles
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
                'I.id',
                'D.topic',
                'D.description',
                'I.alias',
                'I.picture',
                'I.published_date',
                'I.created_at',
                'I.visited',
                'I.category_id'
            )
            ->orderBy('I.published_date', 'DESC')
            ->orderBy('I.id', 'DESC')
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
