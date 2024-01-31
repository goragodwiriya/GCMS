<?php
/**
 * @filesource modules/video/models/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Video\View;

/**
 * โมเดลสำหรับแสดงรายการอัลบัม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ตรวจสอบวีดีโอที่เลือก
     *
     * @param int $id
     *
     * @return object
     */
    public static function get($id)
    {
        $model = new static;
        $search = $model->db()->createQuery()
            ->from('video V')
            ->join('modules M', 'INNER', array('M.id', 'V.module_id'))
            ->where(array('V.id', (int) $id))
            ->cacheOn()
            ->toArray()
            ->first('V.*', 'M.config');
        if ($search) {
            $config = @unserialize($search['config']);
            unset($search['config']);
            foreach ($config as $key => $value) {
                $search[$key] = $value;
            }
            return (object) $search;
        }
        return null;
    }

    /**
     * อัปเดตการเปิดดูจาก Youtube
     *
     * @param int $id
     * @param int $views
     */
    public static function updateView($id, $views)
    {
        $model = new static;
        $search = $model->db()->update($model->getTableName('video'), (int) $id, array('views' => $views));
    }
}
