<?php
/**
 * @filesource widgets/download/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Download\Models;

/**
 * Download widget model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * Get latest download items by module.
     *
     * @param int $module_id
     * @param int $limit
     *
     * @return array
     */
    public static function getLatest($module_id, $limit = 5)
    {
        return static::createQuery()
            ->select('id', 'module_id', 'category_id', 'name', 'detail', 'ext', 'updated_at', 'downloads', 'size', 'file')
            ->from('download')
            ->where(['module_id', (int) $module_id])
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(max(1, min(20, (int) $limit)))
            ->cacheOn()
            ->fetchAll();
    }

    /**
     * Get one download item by ID.
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        return static::createQuery()
            ->select('id', 'module_id', 'name', 'detail', 'ext', 'downloads', 'size', 'file')
            ->from('download')
            ->where(['id', (int) $id])
            ->cacheOn()
            ->first();
    }
}
