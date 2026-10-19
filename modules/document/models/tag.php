<?php
/**
 * @filesource modules/document/models/tag.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\Tag;

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
            ['T.tag', $index->alias],
            ['I.index', 0],
            ['I.published', 1],
            ['I.published_date', '<=', date('Y-m-d')]
        ];

        $query = \Kotchasan\Model::createQuery()
            ->from('index_tag T')
            ->join('index I', ['I.id', 'T.index_id'])
            ->join('modules M', ['M.id', 'I.module_id'])
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', ['', LANGUAGE]]])
            ->join('category C', [['C.type', 'category'], ['C.module_id', 'I.module_id'], ['C.category_id', 'I.category_id']], 'LEFT')
            ->where($where)
            ->cacheOn();

        $obj->instance = $query;

        return $obj;
    }

    /**
     * Count total articles matching current filters
     *
     * @return int
     */
    public function count(): int
    {
        $query = clone $this->instance;
        $row = $query->selectCount('I.id')->first();
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
                'C.topic category',
                'M.module'
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

    /**
     * @param $tag
     */
    public function updateCount($tag)
    {
        return \Kotchasan\DB::create()->increment('tags', ['tag', $tag], 'count');
    }
}
