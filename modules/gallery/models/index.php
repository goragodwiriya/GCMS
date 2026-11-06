<?php
/**
 * @filesource modules/gallery/models/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gallery\Index;

/**
 * Gallery Index Model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Read Album detail with images
     *
     * @param object $index
     *
     * @return object|false
     */
    public static function getAlbum($index)
    {
        if (empty($index->id)) {
            return false;
        }
        $album = static::createQuery()
            ->select('A.*')
            ->from('gallery_album A')
            ->where([
                ['A.id', $index->id],
                ['A.published_date', '<=', date('Y-m-d')]
            ])
            ->cacheOn()
            ->first();
        if ($album) {
            foreach ($album as $key => $value) {
                $index->$key = $value;
            }
            // images
            $index->images = static::createQuery()
                ->select('id', 'image')
                ->from('gallery_image')
                ->where(['album_id', $album->id])
                ->orderBy('count', 'ASC')
                ->cacheOn()
                ->fetchAll();
            return $index;
        }
        return false;
    }
}
