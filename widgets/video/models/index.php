<?php
/**
 * @filesource widgets/video/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Video\Models;

/**
 * Widget Video Model – pulls up the latest videos.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index
{
    /**
     * Retrieve the latest videos for display in the widget.
     *
     * @param int $module_id
     * @param int $limit
     *
     * @return array
     */
    public static function getLatest($module_id, $limit = 6)
    {
        return \Kotchasan\Model::createQuery()
            ->select('id', 'youtube', 'topic', 'views', 'last_update')
            ->from('video')
            ->where(['module_id', $module_id])
            ->orderBy('last_update', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();
    }
}
