<?php
/**
 * @filesource modules/index/models/comment.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Comment;

use Kotchasan\Database\Sql;

/**
 *  Model สำหรับแสดงรายการความคิดเห็น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * รายการแสดงความคิดเห็น
     *
     * @param object $story
     * @param string $table ชื่อตารางที่ต้องการอ่านความคิดเห็น ถ้าไม่ระบุอ่านจากตาราง comment
     *
     * @return array
     */
    public static function get($story, $table = 'comment')
    {
        $model = new static;
        return $model->db()->createQuery()
            ->select('C.*', 'U.status', Sql::create("(CASE WHEN ISNULL(U.`id`) THEN C.`email` WHEN U.`displayname`='' THEN U.`email` ELSE U.`displayname` END) AS `displayname`"))
            ->from($table.' C')
            ->join('user U', 'LEFT', array('U.id', 'C.member_id'))
            ->where(array(array('C.index_id', (int) $story->id), array('C.module_id', (int) $story->module_id)))
            ->order('C.id')
            ->cacheOn()
            ->execute();
    }

    /**
     * อัปเดตจำนวนความคิดเห็น
     *
     * @param int $qid       ID ของบทความ
     * @param int $module_id ID ของโมดูล
     */
    public static function update($qid, $module_id)
    {
        $model = new static;
        $count = $model->db()->createQuery()
            ->selectCount()
            ->from('comment')
            ->where(array(array('index_id', $qid), array('module_id', $module_id)));
        $model->db()->createQuery()
            ->update('index')
            ->set(array('comments' => $count))
            ->where(array('id', $qid))
            ->execute();
    }
}
