<?php
/**
 * @filesource widgets/gallery/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Gallery\Models;

/**
 * Widget Gallery Album Model – pulls up the latest albums
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index
{
    /**
     * Retrieve the latest published albums for display in the widget.
     *
     * @param int $module_id
     * @param int $limit
     *
     * @return array
     */
    public static function getLatest($module_id, $limit = 6)
    {
        return \Kotchasan\Model::createQuery()
            ->select('A.id', 'A.module_id', 'A.topic', 'A.detail', 'G.image', 'A.published_date')
            ->from('gallery_album A')
            ->join('gallery_image G', [['G.album_id', 'A.id'], ['G.count', 0]], 'LEFT')
            ->where([
                ['A.module_id', $module_id],
                ['A.published_date', '<=', date('Y-m-d')]
            ])
            ->orderBy('A.published_date', 'DESC')
            ->orderBy('A.id', 'DESC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();
    }
}
