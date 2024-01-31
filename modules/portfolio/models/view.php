<?php
/**
 * @filesource modules/portfolio/models/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Portfolio\View;

use Kotchasan\Http\Request;

/**
 * โมเดลสำหรับแสดงรายการที่เลือก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * query รายการที่เลือก
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function get(Request $request, $index)
    {
        $model = new static;
        // query
        $result = $model->db()->createQuery()
            ->from('portfolio')
            ->where(array(
                array('id', $request->request('id')->toInt()),
                array('module_id', $index->module_id)
            ))
            ->cacheOn(false)
            ->toArray()
            ->first('id', 'title', 'image', 'detail', 'create_date', 'url', 'visited', 'keywords');
        if ($result) {
            // visited
            ++$result['visited'];
            $model->db()->update($model->getTableName('portfolio'), $result['id'], array('visited' => $result['visited']));
            $model->db()->cacheSave(array($result));
            // คืนค่า
            foreach ($result as $key => $value) {
                $index->$key = $value;
            }
            return $index;
        }
        return null;
    }
}
