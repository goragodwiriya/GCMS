<?php
/**
 * @filesource widgets/tags/models/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Models;

use Kotchasan\Database\Sql;

/**
 * Tags Widget — Table Model
 *
 * Database queries for the Tags widget settings.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Settings extends \Kotchasan\Model
{
    /**
     * Return the base query for the DataTable.
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function get()
    {
        $result = static::createQuery()
            ->select()
            ->from('tags')
            ->fetchAll();
        if (empty($result)) {
            $result = [
                (object) [
                    'id' => 1,
                    'tag' => '',
                    'count' => 0
                ]
            ];
        }
        return $result;
    }

    /**
     * Fetch all tags
     *
     * @param int $limit Optional limit for number of tags to return (0 = no limit)
     *
     * @return array
     */
    public static function all($limit = 0)
    {
        return static::createQuery()
            ->select()
            ->from('tags')
            ->orderBy('count', 'DESC')
            ->limit($limit)
            ->cacheOn()
            ->fetchAll();
    }

    /**
     * Update a tag by ID
     *
     * @param int $id
     * @param string $tag
     *
     * @return bool
     */
    public static function updateTag($id, $tag)
    {
        return static::createQuery()
            ->update('tags')
            ->set([
                'tag' => $tag,
                'count' => Sql::create('`count` + 1')
            ])
            ->where('id', $id)
            ->execute();
    }

    /**
     * Increment count for a tag by ID (fire-and-forget from frontend click)
     *
     * @param int $id Tag ID
     *
     * @return bool
     */
    public static function incrementCount($id)
    {
        return static::createQuery()
            ->update('tags')
            ->set(['count' => Sql::create('`count` + 1')])
            ->where('id', $id)
            ->execute();
    }

    /**
     * Make sure every given tag exists as a row in the tags table.
     * Used to keep the tag cloud / management list in sync with tags
     * actually used on documents (index_tag).
     *
     * @param array $tags
     */
    public static function ensureExists(array $tags)
    {
        $tags = array_values(array_unique(array_filter($tags, function ($tag) {
            return $tag !== '';
        })));
        if (empty($tags)) {
            return;
        }
        $existing = static::createQuery()
            ->select('tag')
            ->from('tags')
            ->where(['tag', $tags])
            ->fetchAll();
        $newTags = array_diff($tags, array_map(function ($row) {
            return $row->tag;
        }, $existing));
        if (empty($newTags)) {
            return;
        }
        $db = \Kotchasan\DB::create();
        foreach ($newTags as $tag) {
            $db->insert('tags', [
                'tag' => $tag,
                'count' => 0
            ]);
        }
    }

    /**
     * Return all tags as {value, text} options for autocomplete sources.
     *
     * @param int $limit 0 = no limit
     *
     * @return array
     */
    public static function toOptions($limit = 0)
    {
        $map = [];
        foreach (static::all($limit) as $row) {
            $map[$row->tag] = $row->tag;
        }
        return \Gcms\Controller::arrayToOptions($map);
    }
}
