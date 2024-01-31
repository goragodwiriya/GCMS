<?php
/**
 * @filesource Widgets/Album/Models/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Album\Models;

/**
 * อ่านรายการอัลบัมทั้งหมด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * อ่านรายการอัลบัม
     *
     * @param array $query_string ข้อมูลที่เรียก query string
     *
     * @return array
     */
    public static function get($query_string)
    {
        // query อัลบัมล่าสุด
        return static::createQuery()
            ->select('C.id', 'C.topic', 'G.image', 'M.module')
            ->from('gallery_album C')
            ->join('gallery G', 'INNER', array(array('G.album_id', 'C.id'), array('G.module_id', 'C.module_id'), array('G.count', 0)))
            ->join('modules M', 'INNER', array('M.id', 'C.module_id'))
            ->order('C.id DESC')
            ->limit($query_string['rows'] * $query_string['cols'])
            ->cacheOn()
            ->execute();
    }
}
