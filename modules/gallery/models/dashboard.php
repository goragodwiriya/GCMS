<?php
/**
 * @filesource modules/gallery/models/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Dashboard;

use Kotchasan\Database\Sql;

/**
 * Gallery Dashboard Model
 *
 * Provides gallery statistics for the main dashboard.
 * This file is auto-discovered by Index\Dashboard\Model::getModuleStats().
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Get gallery statistics for the dashboard widget.
     *
     * @return array
     */
    public static function getStats()
    {
        $albums = static::createQuery()
            ->selectCount()
            ->from('gallery_album')
            ->first();

        $images = static::createQuery()
            ->select(Sql::SUM('count', 'total'))
            ->from('gallery_image')
            ->first();

        return [
            'albums' => $albums ? (int) $albums->count : 0,
            'images' => $images ? (int) $images->total : 0
        ];
    }
}
