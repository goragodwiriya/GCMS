<?php
/**
 * @filesource modules/download/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Download\Index;

/**
 * Download Lists Model
 *
 * Used by frontend listing and widgets
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
     * @var object
     */
    private $index;

    /**
     * Create a listing model for a module.
     *
     * @param object $index
     *
     * @return static
     */
    public static function create($index)
    {
        $obj = new static();

        $where = [
            ['module_id', (int) $index->module_id]
        ];

        $categoryIds = self::normalizeCategoryIds($index->category_id ?? []);
        if (!empty($categoryIds)) {
            $where[] = ['category_id', $categoryIds];
        }

        $query = \Kotchasan\Model::createQuery()
            ->from('download')
            ->where($where)
            ->cacheOn();

        $obj->instance = $query;
        $obj->index = $index;

        return $obj;
    }

    /**
     * Count total rows.
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
     * Paginate download items.
     *
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    public function paginate($page, $limit)
    {
        $total = $this->count();
        $total_pages = $limit > 0 ? (int) ceil($total / $limit) : 1;
        $page = max(1, min($page, max(1, $total_pages)));
        $offset = ($page - 1) * $limit;

        $query = clone $this->instance;
        $query->select('id', 'module_id', 'category_id', 'member_id', 'detail', 'updated_at', 'name', 'ext', 'size', 'file', 'downloads', 'reciever');

        $sort = (int) ($this->index->config->sort ?? 1);
        if ($sort === 0) {
            $query->orderBy('id', 'DESC');
        } elseif ($sort === 2) {
            $query->orderBy('RAND()');
        } else {
            $query->orderBy('updated_at', 'DESC');
            $query->orderBy('id', 'DESC');
        }

        $items = $query
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
     * Normalize category filter to integer array.
     *
     * @param mixed $categoryIds
     *
     * @return array
     */
    private static function normalizeCategoryIds($categoryIds)
    {
        if (!is_array($categoryIds)) {
            if ($categoryIds === '' || $categoryIds === null) {
                return [];
            }
            $categoryIds = explode(',', (string) $categoryIds);
        }

        $result = [];
        foreach ($categoryIds as $id) {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $result, true)) {
                $result[] = $id;
            }
        }

        return $result;
    }
}
