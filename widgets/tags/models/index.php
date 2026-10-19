<?php
/**
 * @filesource widgets/tags/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Models;

/**
 * Model สำหรับลิสต์รายการ Tag
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * query รายการ tag ทั้งหมด
     * เรียงลำดับตาม count
     *
     * @return array
     */
    public static function all()
    {
        return static::createQuery()
            ->select()
            ->from('tags')
            ->orderBY('count')
            ->cacheOn()
            ->fetchAll();
    }
}
