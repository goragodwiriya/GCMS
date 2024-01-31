<?php
/**
 * @filesource Widgets/Video/Models/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Video\Models;

/**
 * Controller หลัก สำหรับแสดงผล Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * @param  $id
     * @param  $count
     *
     * @return mixed
     */
    public static function get($id, $count)
    {
        $model = new static;
        $query = $model->db()->createQuery()
            ->select('id', 'topic', 'youtube')
            ->from('video');
        if ($id > 0) {
            $query->where(array('id', $id));
        } else {
            $query->order('id DESC');
        }
        return $query->limit($count)
            ->toArray()
            ->cacheOn()
            ->execute();
    }
}
