<?php
/**
 * @filesource modules/index/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Index;

/**
 * index table
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Read Index module information
     *
     * @param object $index
     *
     * @return object|false Return object data, or false if not found
     */
    public static function get($index)
    {
        $where = [];
        if (isset($index->id)) {
            $where[] = ['I.id', (int) $index->id];
        } elseif (isset($index->module_id) && isset($index->index_id)) {
            $where[] = ['I.module_id', (int) $index->module_id];
            $where[] = ['I.id', (int) $index->index_id];
        } elseif (isset($index->index_id)) {
            $where[] = ['I.id', (int) $index->index_id];
        }
        if (empty($where)) {
            return false;
        }
        $where[] = ['I.index', 1];
        $where[] = ['I.published', 1];
        $where[] = ['I.published_date', '<=', date('Y-m-d')];
        // Create a query with the cache turned on but not auto-save so that the cache will be saved after the update is visited.
        $query = static::createQuery()
            ->select('I.id index_id', 'I.module_id', 'M.module', 'D.topic', 'D.keywords', 'D.detail', 'D.description', 'I.visited')
            ->from('index I')
            ->join('modules M', ['M.id', 'I.module_id'])
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', ['', LANGUAGE]]])
            ->where($where)
            ->orderBy('D.language', 'DESC')
            ->cacheOn(false);
        $result = $query->first();
        if ($result) {
            // Update visited count in cache
            $result->visited++;
            $query->saveCache($result);
            // Increment visited count atomically
            \Kotchasan\DB::create()->increment(
                'index',
                [['id', $result->index_id], ['module_id', $result->module_id]],
                'visited'
            );

            // return data
            $index->keywords = $result->keywords;
            $index->detail = $result->detail;
            $index->description = $result->description;
            $index->visited = $result->visited;
            $index->module_id = $result->module_id;
            $index->module = $result->module;
            $index->index_id = $result->index_id;
            $index->topic = $result->topic;
            return $index;
        }
        return false;
    }

    /**
     * Get detail content and increment visited counter
     *
     * @param int $index_id
     * @param int $module_id
     *
     * @return string Detail content, or empty string if not found
     */
    public static function getDetail($index_id, $module_id)
    {
        $db = \Kotchasan\DB::create();
        // get detail
        $detail = $db->first('index_detail', [['id', $index_id], ['module_id', $module_id], ['language', [LANGUAGE, '']]], ['detail']);
        // Increment view counter
        $db->increment('index', [['id', $index_id], ['module_id', $module_id], ['index', 1]], ['visited', 'visited_today']);
        // return detail
        return $detail ? $detail->detail : '';
    }
}
