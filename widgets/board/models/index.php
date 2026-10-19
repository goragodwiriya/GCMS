<?php
/**
 * @filesource widgets/board/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Board\Models;

/**
 * Board Widget Model
 *
 * Fetches latest published board topics
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * Get latest published topics
     *
     * @param int $module_id
     * @param int $limit      Max results
     *
     * @return array
     */
    public static function getLatest($module_id, $limit = 5)
    {
        return static::createQuery()
            ->select(
                'Q.id',
                'Q.topic',
                'Q.category_id',
                'U.name AS sender',
                'U.status',
                'Q.created_at',
                'Q.comments',
                'Q.module_id'
            )
            ->from('board_q Q')
            ->join('user U', ['U.id', 'Q.member_id'], 'LEFT')
            ->where([
                ['Q.module_id', $module_id],
                ['Q.published', 1]
            ])
            ->orderBy('Q.updated_at', 'DESC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();
    }
}
