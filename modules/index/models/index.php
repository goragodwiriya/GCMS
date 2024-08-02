<?php
/**
 * @filesource modules/index/models/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Index;

use Kotchasan\Date;

/**
 * ตาราง index
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Orm\Field
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'index I';

    /**
     * อ่านข้อมูลโมดูล Index
     *
     * @param object $index
     *
     * @return object|false คืนค่าข้อมูล object ไม่พบ คืนค่า false
     */
    public static function get($index)
    {
        $model = new \Kotchasan\Model();
        if (isset($index->id)) {
            $where = [
                ['I.id', (int) $index->id]
            ];
        } elseif (isset($index->index_id)) {
            $where = [
                ['I.id', (int) $index->index_id]
            ];
        } elseif (isset($index->module_id) && isset($index->index_id)) {
            $where = [
                ['I.module_id', (int) $index->module_id],
                ['I.id', (int) $index->index_id]
            ];
        }
        $where[] = ['I.index', 1];
        $where[] = ['I.published', 1];
        $where[] = ['I.published_date', '<=', date('Y-m-d')];
        $result = $model->db()->createQuery()
            ->from('index I')
            ->join('modules M', 'INNER', ['M.id', 'I.module_id'])
            ->join('index_detail D', 'INNER', [['D.id', 'I.id'], ['D.module_id', 'I.module_id'], ['D.language', 'I.language']])
            ->where($where)
            ->toArray()
            ->cacheOn(false)
            ->first('I.id index_id', 'I.module_id', 'M.module', 'D.topic', 'D.keywords', 'D.detail', 'D.description', 'I.visited');
        if ($result) {
            // อัปเดตการเยี่ยมชม
            ++$result['visited'];
            $model->db()->cacheSave([$result]);
            $model->db()->update($model->getTableName('index'), $result['index_id'], ['visited' => $result['visited']]);
            // คืนค่า
            $index->keywords = $result['keywords'];
            $index->detail = $result['detail'];
            $index->description = $result['description'];
            $index->visited = $result['visited'];
            $index->module_id = $result['module_id'];
            $index->module = $result['module'];
            $index->index_id = $result['index_id'];
            $index->topic = $result['topic'];
            return $index;
        }
        return false;
    }
}
