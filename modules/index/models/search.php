<?php
/**
 * @filesource modules/index/models/search.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Search;

use Gcms\Gcms;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * search model
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค้นหาข้อมูลทั้งหมด
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function findAll(Request $request, $index)
    {
        // model
        $model = new static;
        $db = $model->db();
        // ข้อความค้นหา จำกัดไม่เกิน 100 ตัวอักษร
        $index->q = mb_substr($request->globals(array('POST', 'GET'), 'q')->topic(), 0, 255);
        $index->words = array();
        $where1 = array();
        $where2 = array();
        $score1 = array();
        $score2 = array();
        // แยกข้อความค้นหาออกเป็นคำๆ ค้นหาข้อความที่มีความยาวมากกว่า 1 ตัวอักษร
        $q = '(CASE WHEN M.`owner`="index" THEN 102 WHEN M.`owner`="board" THEN 100 ELSE 101 END)';
        foreach (explode(' ', $index->q) as $item) {
            if (mb_strlen($item) > 1) {
                $index->words[] = $item;
                $where1[] = array('D.topic', 'LIKE', '%'.$item.'%');
                $where1[] = array('D.detail', 'LIKE', '%'.$item.'%');
                $score1[] = "MATCH (D.`topic`) AGAINST('$item') + MATCH (D.`detail`) AGAINST('$item') + $q";
                $where2[] = array('C.detail', 'LIKE', '%'.$item.'%');
                $score2[] = "MATCH (C.`detail`) AGAINST('$item')";
            }
        }
        if (!empty($where1)) {
            $index->sqls = array();
            $select = array('I.id', 'I.alias', 'M.module', 'M.owner', 'D.topic', 'D.description', 'I.visited', 'I.index');
            $q1 = $db->createQuery()
                ->select($select, Sql::create('('.implode(' + ', $score1).') AS `score`'))
                ->from('modules M')
                ->join('index I', 'INNER', array(array('I.module_id', 'M.id'), array('I.published', 1), array('I.published_date', '<=', date('Y-m-d')), array('I.language', array(Language::name(), ''))))
                ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'M.id')))
                ->where($where1, 'OR');
            $q2 = $db->createQuery()
                ->select($select, Sql::create('('.implode(' + ', $score2).') AS `score`'))
                ->from('comment C')
                ->join('modules M', 'INNER', array('M.id', 'C.module_id'))
                ->join('index I', 'INNER', array(array('I.module_id', 'M.id'), array('I.published', 1), array('I.published_date', '<=', date('Y-m-d')), array('I.language', array(Language::name(), ''))))
                ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'M.id')))
                ->where($where2, 'OR');
            // union all queries
            $q3 = $db->createQuery()->union($q1, $q2);
            // groub by id
            $index->sqls[] = $db->createQuery()->select()->from(array($q3, 'Q'))->groupBy('Q.id');
            // ค้นหาจากโมดูลอื่นๆที่ติดตั้ง
            foreach (Gcms::$module->getInstalledOwners() as $item => $modules) {
                if ($item != 'index' && is_file(ROOT_PATH."modules/$item/models/search.php")) {
                    include ROOT_PATH."modules/$item/models/search.php";
                    $className = ucfirst($item).'\Search\Model';
                    if (method_exists($className, 'findAll')) {
                        createClass($className)->findAll($request, $index);
                    }
                }
            }
            // union all queries
            $query = $db->createQuery()->from(array($db->createQuery()->union($index->sqls), 'Z'));
            // จำนวน
            $index->total = $query->cacheOn()->count();
        } else {
            $index->total = 0;
        }
        // ข้อมูลแบ่งหน้า
        if (empty($index->list_per_page)) {
            $index->list_per_page = 20;
        }
        $index->page = $request->request('page')->toInt();
        $index->totalpage = ceil($index->total / $index->list_per_page);
        $index->page = max(1, ($index->page > $index->totalpage ? $index->totalpage : $index->page));
        $index->start = $index->list_per_page * ($index->page - 1);
        $index->end = ($index->start + $index->list_per_page > $index->total) ? $index->total : $index->start + $index->list_per_page;
        if (!empty($where1)) {
            // query
            $index->items = $query->select()
                ->order('score DESC', 'visited DESC')
                ->limit($index->list_per_page, $index->start)
                ->cacheOn()
                ->execute();
        } else {
            $index->items = array();
        }
        return $index;
    }
}
