<?php
/**
 * @filesource modules/gallery/models/write.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Write;

/**
 * Gallery Album API Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get album data (for admin edit form)
     *
     * @param int $id  0 = new album
     * @param int $module_id  Module ID for new album
     *
     * @return object|null  Album data object or null if not found
     */
    public static function get($id, $module_id)
    {
        if ($id === 0) {
            return (object) [
                'id' => 0,
                'module_id' => $module_id,
                'topic' => '',
                'detail' => '',
                'published_date' => date('Y-m-d'),
                'count' => 0,
                'images' => []

            ];
        }

        $album = static::createQuery()
            ->select()
            ->from('gallery_album')
            ->where(['id', $id])
            ->first();

        if (!$album) {
            return null;
        }

        $query = static::createQuery()
            ->select('id', 'image', 'count')
            ->from('gallery_image')
            ->where([
                ['album_id', $id],
                ['module_id', $album->module_id]
            ])
            ->orderBy('count', 'ASC');
        $images = [];
        $count = 0;
        foreach ($query->fetchAll() as $img) {
            $images[] = ['url' => WEB_URL.DATA_FOLDER.'gallery/'.$album->id.'/'.$img->image, 'name' => $img->image];
            $count = max($count, $img->count);
        }
        $album->count = $count + 1;
        $album->images = $images;

        return $album;
    }
}
