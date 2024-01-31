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
 * กระทู้ทั้งหมด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * กระทู้ทั้งหมด
     *
     * @param array $ids แอเรย์ของ module_id
     *
     * @return array
     */
    public static function getStories($ids)
    {
        return \Kotchasan\Model::createQuery()
            ->select('id', 'module_id', 'last_update', 'comment_date')
            ->from('board_q')
            ->where(array('module_id', $ids))
            ->cacheOn()
            ->execute();
    }
}
