<?php
/**
 * @filesource modules/document/models/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Sitemap;

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
    public static function getStories($ids, $date)
    {
        return \Kotchasan\DB::create()->select(
            'index',
            [['module_id', $ids], ['index', 0], ['published', 1], ['published_date', '<=', $date]],
            ['cache' => true],
            ['id', 'module_id', 'alias', 'published_date']
        );
    }
}
