<?php
/**
 * @filesource modules/board/models/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Sitemap;

/**
 * All articles
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * All articles
     *
     * @param array  $ids  Array of module_id
     * @param string $date Today's date
     *
     * @return array
     */
    public static function getStories($ids)
    {
        return \Kotchasan\DB::create()->select(
            'board_q',
            ['module_id', $ids],
            ['cache' => true],
            ['id', 'module_id', 'updated_at', 'comment_date']
        );
    }
}
