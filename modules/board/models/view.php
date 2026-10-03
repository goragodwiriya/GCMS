<?php
/**
 * @filesource modules/board/models/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Board\View;

/**
 * Board Topic Detail Model — Frontend single topic with replies
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get a single topic with its replies for frontend display
     * Also increments visited counter
     *
     * @param object $index Module data (mutated in place)
     *
     * @return object|null
     */
    public static function get($index)
    {
        $query = static::createQuery()
            ->select(
                'Q.id',
                'Q.module_id',
                'Q.category_id',
                'Q.topic',
                'Q.detail',
                'Q.picture',
                'Q.published',
                'Q.pin',
                'Q.locked',
                'U.name sender',
                'Q.member_id',
                'Q.created_at',
                'Q.updated_at',
                'Q.visited',
                'Q.comments',
                'C.topic AS category_name'
            )
            ->from('board_q Q')
            ->join('user U', ['U.id', 'Q.member_id'], 'LEFT')
            ->join('category C', [['C.category_id', 'Q.category_id'], ['C.module_id', 'Q.module_id'], ['C.type', "category"]], 'LEFT')
            ->where([
                ['Q.id', $index->id],
                ['Q.module_id', $index->module_id],
                ['Q.published', 1]
            ])
            ->cacheOn(false);
        $result = $query->first();

        if (!$result) {
            return null;
        }

        $result->visited++; // Increment visited count for cache
        $query->saveCache($result);

        // Restore stored entities
        $result->detail = str_replace(
            ['&#x007B;', '&#x007D;', '&#92;', '{WEBURL}'],
            ['{', '}', '\\', WEB_URL],
            $result->detail
        );
        $categories = json_decode((string) $result->category_name, true) ?: '';
        $result->category_name = $categories[LANGUAGE] ?? $categories[''] ?? '';

        // Increment view counter atomically to avoid race conditions
        \Kotchasan\DB::create()->increment('board_q', ['id', $result->id], ['visited']);

        // Get all replies
        $replies = static::createQuery()
            ->select(
                'R.id',
                'R.member_id',
                'U.name sender',
                'R.detail',
                'R.picture',
                'R.updated_at'
            )
            ->from('board_r R')
            ->join('user U', ['U.id', 'R.member_id'], 'LEFT')
            ->where(['R.index_id', $index->id])
            ->orderBy('R.updated_at', 'ASC')
            ->fetchAll();

        foreach ($replies as $reply) {
            $reply->detail = str_replace('{WEBURL}', WEB_URL, $reply->detail);
        }

        $index->replies = $replies;

        // Copy meta to index object
        $index->topic_data = $result;

        return $index;
    }
}
