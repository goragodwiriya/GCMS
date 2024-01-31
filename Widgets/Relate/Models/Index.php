<?php
/**
 * @filesource Widgets/Relate/Models/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Relate\Models;

use Kotchasan\Database\Sql;
use Kotchasan\Date;
use Kotchasan\Language;

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
     * อ่านข้อมูล
     *
     * @param int $id
     * @param int $rows
     * @param int $cols
     *
     * @return object
     */
    public static function get($id, $rows, $cols)
    {
        if ($id > 0) {
            // อ่านโมดูล จาก id ของ บทความ
            $index = static::createQuery()
                ->from('index Q')
                ->join('index_detail D', 'INNER', array(
                    array('D.id', 'Q.id'),
                    array('D.module_id', 'Q.module_id'),
                    array('D.language', array(Language::name(), ''))
                ))
                ->where(array(
                    array('Q.id', $id),
                    array('Q.index', '0')
                ))
                ->toArray()
                ->cacheOn()
                ->first('D.relate', 'Q.id', 'Q.module_id');
            if ($index && $index['relate'] !== '') {
                // relate
                $qs = array();
                foreach (explode(',', $index['relate']) as $q) {
                    $qs[] = "D.`relate` LIKE '%$q%'";
                }
                $select = array('Q.id', 'D.topic', 'Q.alias', 'Q.picture', 'Q.comment_date', 'Q.last_update', 'Q.create_date', 'D.description', 'Q.comments', 'Q.visited', 'Q.member_id', 'D.language');
                $where = array(
                    array('Q.module_id', (int) $index['module_id']),
                    array('Q.published', '1'),
                    array('Q.published_date', '<=', date('Y-m-d')),
                    array('Q.index', '0'),
                    array('Q.id', '>', $id),
                    Sql::create('('.implode(' OR ', $qs).')'),
                    array('D.language', array(Language::name(), '')),
                    array('Q.id', '!=', $id)
                );
                $limit = $rows * $cols;
                // newest
                $q1 = static::createQuery()
                    ->select($select)
                    ->from('index Q')
                    ->join('index_detail D', 'INNER', array(array('D.id', 'Q.id'), array('D.module_id', 'Q.module_id')))
                    ->where($where)
                    ->order('Q.create_date');
                $sql1 = 'SELECT @n:=@n+1 AS `row`,Q.* FROM ('.$q1->text().') AS Q, (SELECT @n:=0) AS R';
                // older
                $where[4][1] = '<';
                $q1->select($select)
                    ->where($where)
                    ->order('Q.create_date DESC');
                $sql2 = 'SELECT @m:=@m+1 AS `row`,Q.* FROM ('.$q1->text().') AS Q, (SELECT @m:=0) AS L';
                $sql3 = static::createQuery()
                    ->select()
                    ->from(array("($sql1) UNION ($sql2)", 'N'))
                    ->groupBy('N.id')
                    ->order('N.row')
                    ->limit($limit);
                $query = static::createQuery()
                    ->select('Y.id', 'Y.topic', 'Y.alias', 'Y.picture', 'Y.comment_date', 'Y.last_update', 'Y.create_date', 'Y.description', 'Y.comments', 'Y.visited')
                    ->from(array($sql3, 'Y'))
                    ->order('Y.create_date');
                $index['items'] = $query->cacheOn()->execute();
                $index['cols'] = $cols;
                // คืนค่า HTML
                return (object) $index;
            }
        }
        return false;
    }
}
