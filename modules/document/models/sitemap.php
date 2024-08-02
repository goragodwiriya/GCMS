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
 * บทความทั้งหมด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * บทความทั้งหมด
     *
     * @param array  $ids  แอเรย์ของ module_id
     * @param string $date วันที่วันนี้
     *
     * @return array
     */
    public static function getStories($ids, $date)
    {
        $model = new static;
        return $model->db()->createQuery()
            ->select('id', 'module_id', 'alias', 'create_date')
            ->from('index')
            ->where([['module_id', $ids], ['index', 0], ['published', 1], ['published_date', '<=', $date]])
            ->cacheOn()
            ->execute();
    }
}
