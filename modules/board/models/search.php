<?php
/**
 * @filesource modules/board/models/search.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Search;

use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

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
    public function findAll(Request $request, $index)
    {
        $where1 = [];
        $where2 = [];
        $score1 = [];
        $score2 = [];
        // ค้นหาข้อมูล
        foreach ($index->words as $item) {
            $where1[] = ['Q.topic', 'LIKE', '%'.$item.'%'];
            $where1[] = ['Q.detail', 'LIKE', '%'.$item.'%'];
            $where2[] = ['R.detail', 'LIKE', '%'.$item.'%'];
            $score1[] = "MATCH (Q.`topic`) AGAINST('$item') + MATCH (Q.`detail`) AGAINST('$item')";
            $score2[] = "MATCH (R.`detail`) AGAINST('$item')";
        }
        $db = $this->db();
        $q1 = $db->createQuery()
            ->select('Q.id', 'Q.topic alias', 'M.module', 'M.owner', 'Q.topic', 'Q.detail description', 'Q.visited', '0 index', Sql::create('('.implode(' + ', $score1).') AS `score`'))
            ->from('board_q Q')
            ->join('modules M', 'INNER', [['M.id', 'Q.module_id'], ['M.owner', 'board']])
            ->where($where1);
        $q2 = $db->createQuery()
            ->select('Q.id', 'Q.topic alias', 'M.module', 'M.owner', 'Q.topic', 'R.detail description', 'Q.visited', '0 index', Sql::create('('.implode(' + ', $score2).') AS `score`'))
            ->from('board_r R')
            ->join('board_q Q', 'INNER', [['Q.id', 'R.index_id'], ['Q.module_id', 'R.module_id']])
            ->join('modules M', 'INNER', [['M.id', 'Q.module_id'], ['M.owner', 'board']])
            ->where($where2);
        // union all queries
        $q3 = $db->createQuery()->union($q1, $q2);
        // groub by id
        $index->sqls[] = $db->createQuery()->select()->from([$q3, 'Y'])->groupBy('Y.id');
    }
}
