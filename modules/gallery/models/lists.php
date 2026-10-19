<?php
/**
 * @filesource modules/gallery/models/lists.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Lists;

/**
 * Gallery Lists Model
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
     * @param array $params
     *
     * @return static
     */
    public static function create($params = [])
    {
        $obj = new static();

        $where = [
            ['A.published_date', '<=', date('Y-m-d')]
        ];
        if (isset($params['module_id'])) {
            $where[] = ['A.module_id', $params['module_id']];
        }

        $query = \Kotchasan\Model::createQuery()
            ->from('gallery_album A')
            ->where($where)
            ->cacheOn();

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([['A.topic', 'LIKE', $search]], 'OR');
        }

        $obj->instance = $query;

        return $obj;
    }

    /**
     * Query albums for widget (latest)
     *
     * @param int $limit
     *
     * @return array
     */
    public function widget($limit)
    {
        $query = clone $this->instance;
        return $query
            ->select('A.id', 'A.module_id', 'A.topic', 'A.detail', 'G.image', 'A.published_date')
            ->join('gallery_image G', [['G.album_id', 'A.id'], ['G.count', 0]], 'LEFT')
            ->orderBy('A.published_date', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Count total albums for pagination
     *
     * @return int
     */
    public function count(): int
    {
        $query = clone $this->instance;
        $row = $query->selectCount('A.id')->first();

        return $row ? (int) $row->count : 0;
    }

    /**
     * Paginate albums
     *
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    public function paginate($page, $limit)
    {
        $total = $this->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $limit;

        $query = clone $this->instance;
        $items = $query
            ->select('A.id', 'A.module_id', 'G.image', 'A.topic', 'A.detail', 'A.published_date')
            ->join('gallery_image G', [['G.album_id', 'A.id'], ['G.count', 0]], 'LEFT')
            ->orderBy('A.published_date', 'DESC')
            ->limit($limit, $offset)
            ->fetchAll();

        return [
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages
        ];
    }

    /**
     * Query galleries for sitemap
     *
     * @return array
     */
    public function sitemap()
    {
        return $this->instance->select('A.id', 'A.published_date created_at', 'M.module')
            ->join('modules M', ['M.id', 'A.module_id'])
            ->fetchAll();
    }
}
