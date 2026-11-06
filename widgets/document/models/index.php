<?php
/**
 * @filesource widgets/document/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Document\Models;

/**
 * Widget Document Model – pulls the latest articles
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * Retrieve the latest published article
     *
     * @param int   $module_id
     * @param int   $limit
     * @param array $category_ids  Restrict to these category_id, empty for every category
     *
     * @return array
     */
    public static function getLatest($module_id, $limit = 5, $category_ids = [])
    {
        $where = [
            ['I.module_id', $module_id],
            ['I.index', 0],
            ['I.published', 1],
            ['I.published_date', '<=', date('Y-m-d')],
            ['D.language', ['', LANGUAGE]]
        ];
        if (!empty($category_ids)) {
            // An array value becomes IN (...) — same as D.language above
            $where[] = ['I.category_id', $category_ids];
        }

        return static::createQuery()
            ->select(
                'D.id',
                'D.topic',
                'D.description',
                'I.alias',
                'I.picture',
                'I.published_date'
            )
            ->from('index I')
            ->join('index_detail D', [['D.id', 'I.id'], ['D.module_id', 'I.module_id']])
            ->where($where)
            ->orderBy('I.published_date', 'DESC')
            ->orderBy('I.id', 'DESC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();
    }
}
