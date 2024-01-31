<?php
/**
 * @filesource modules/board/models/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\View;

use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * อ่านข้อมูลโมดูล
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านกระทู้ที่เลือก
     *
     * @param object $index ข้อมูลที่ส่งมา
     *
     * @return object ข้อมูล object ไม่พบคืนค่า null
     */
    public static function get(Request $request, $index)
    {
        // model
        $model = new static;
        // select
        $fields = array(
            'I.*',
            'U.status',
            'U.id member_id',
            'C.config',
            'C.topic category',
            'C.detail cat_tooltip',
            Sql::create("(CASE WHEN ISNULL(U.`id`) THEN (CASE WHEN I.`sender`='' THEN I.`email` ELSE I.`sender` END) WHEN U.`displayname`='' THEN U.`email` ELSE U.`displayname` END) AS `name`")
        );
        $query = $model->db()->createQuery()
            ->from('board_q I')
            ->join('user U', 'LEFT', array('U.id', 'I.member_id'))
            ->join('category C', 'LEFT', array(array('C.category_id', 'I.category_id'), array('C.module_id', 'I.module_id')))
            ->where(array('I.id', $index->id))
            ->toArray();
        if (!$request->request('visited')->exists()) {
            $query->cacheOn(false);
        }
        $result = $query->first($fields);
        if ($result) {
            // อัปเดตการเยี่ยมชม
            ++$result['visited'];
            $model->db()->update($model->getTableName('board_q'), $result['id'], array('visited' => $result['visited']));
            $model->db()->cacheSave(array($result));
            // อัปเดตตัวแปร
            foreach ($result as $key => $value) {
                switch ($key) {
                    case 'config':
                        $config = @unserialize($value);
                        if (is_array($config)) {
                            foreach ($config as $k => $v) {
                                $index->$k = $v;
                            }
                        }
                        break;
                    default:
                        $index->$key = $value;
                        break;
                }
            }
            // คืนค่าข้อมูลบทความ
            return $index;
        }
        return null;
    }
}
