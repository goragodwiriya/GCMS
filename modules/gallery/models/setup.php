<?php
/**
 * @filesource modules/gallery/models/setup.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Setup;

/**
 * Gallery Albums DataTable Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query for DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        return static::createQuery()
            ->select('A.id', 'A.module_id', 'G.image', 'A.topic', 'A.detail', 'A.published_date', 'A.updated_at')
            ->from('gallery_album A')
            ->join('gallery_image G', [['G.album_id', 'A.id'], ['G.count', 0]], 'LEFT')
            ->where(['A.module_id', $params['module_id']]);
    }

    /**
     * Get images for an album
     * Returns an associative array of image filename => image ID
     *
     * @param int $albumId
     *
     * @return array
     */
    public static function images($albumId, $moduleId)
    {
        $query = static::createQuery()
            ->select('id', 'image')
            ->from('gallery_image')
            ->where([
                ['album_id', $albumId],
                ['module_id', $moduleId]
            ])
            ->orderBy('count', 'ASC');
        $images = [];
        foreach ($query->fetchAll() as $item) {
            $images[$item->image] = $item->id;
        }
        return $images;
    }
}
