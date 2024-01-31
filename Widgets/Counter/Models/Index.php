<?php
/**
 * @filesource Widgets/Counter/Models/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Counter\Models;

/**
 * อ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูล Counter และ Useronline
     *
     * @return object
     */
    public static function get()
    {
        $model = new static;
        $query = $model->db()->createQuery()
            ->selectCount()
            ->from('useronline');
        $ret = $model->db()->createQuery()
            ->from('counter')
            ->order('id DESC')
            ->cacheOn()
            ->first('*', array($query, 'useronline'));
        if (!$ret) {
            return (object) array(
                'counter' => 0,
                'visited' => 0,
                'pages_view' => 0,
                'useronline' => 0
            );
        } else {
            return $ret;
        }
    }
}
