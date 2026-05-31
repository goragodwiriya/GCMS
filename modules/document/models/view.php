<?php
/**
 * @filesource modules/document/models/view.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Document\View;

use Kotchasan\Database\Sql;

/**
 * Document Frontend Model — single article
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get single article for frontend rendering
     *
     * @param object $index Module index data (doc_id must be set)
     *
     * @return object|null
     */
    public static function get($index)
    {
        $where = [];
        if (!empty($index->id)) {
            $where[] = ['I.id', $index->id];
        } elseif (!empty($index->alias)) {
            $where[] = ['I.alias', $index->alias];
        }
        if (empty($where)) {
            return null;
        }
        $where[] = ['I.module_id', $index->module_id];
        $where[] = ['I.index', 0];
        $where[] = ['I.published', 1];
        $where[] = ['D.language', ['', LANGUAGE]];

        $query = static::createQuery()
            ->select(
                'I.id',
                'I.module_id',
                'D.language',
                'I.alias',
                'I.picture',
                'C.category_id',
                'C.topic category_name',
                'D.topic',
                'D.keywords',
                'D.description',
                'D.detail',
                'I.published',
                'I.published_date',
                'I.created_at',
                'I.visited',
                Sql::GROUP_CONCAT('T.tag', 'tags')
            )
            ->from('index I')
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
            ->join('category C', [['C.type', 'category'], ['C.module_id', 'I.module_id'], ['C.category_id', 'I.category_id']], 'LEFT')
            ->join('index_tag T', ['T.index_id', 'I.id'], 'LEFT')
            ->where($where)
            ->orderBy('D.language', 'DESC')
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
        $categories = json_decode($result->category_name ?: '{}', true) ?: [];
        $result->category_name = $categories[LANGUAGE] ?? $categories[''] ?? '';

        // Increment view counter atomically to avoid race conditions
        \Kotchasan\DB::create()->increment('index', ['id', $result->id], ['visited', 'visited_today']);

        // Copy meta to index object
        $index->topic = $result->topic;
        $index->description = $result->description;
        $index->keywords = $result->keywords;
        $index->article = $result;

        return $index;
    }
}
