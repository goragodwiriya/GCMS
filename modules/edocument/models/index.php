<?php
/**
 * @filesource modules/edocument/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Edocument\Index;

/**
 * E-Document frontend listing model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Paginate the documents of a module, newest first.
     *
     * @param int $module_id
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    public static function paginate($module_id, $page, $limit)
    {
        $limit = max(1, $limit);

        $row = static::createQuery()
            ->selectCount()
            ->from('edocument')
            ->where(['module_id', (int) $module_id])
            ->cacheOn()
            ->first();
        $total = $row ? (int) $row->count : 0;
        $total_pages = max(1, (int) ceil($total / $limit));
        $page = max(1, min($page, $total_pages));

        $items = static::createQuery()
            ->select('id', 'sender_id', 'document_no', 'topic', 'ext', 'detail', 'size', 'last_update', 'downloads', 'reciever')
            ->from('edocument')
            ->where(['module_id', (int) $module_id])
            ->orderBy('last_update', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit, ($page - 1) * $limit)
            ->cacheOn()
            ->fetchAll();

        return [
            'page' => $page,
            'total_pages' => $total_pages,
            'total' => $total,
            'items' => $items
        ];
    }
}
