<?php
/**
 * @filesource modules/index/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Home;

use Kotchasan\Database\Sql;
use Kotchasan\Language;

/**
 * ตรวจสอบข้อมูลสมาชิกด้วย Ajax
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * get counter datas
     *
     * @return array
     */
    public static function counter()
    {
        $sql1 = static::createQuery()->selectCount()->from('user');
        $sql4 = static::createQuery()->selectCount()->from('useronline');
        return static::createQuery()
            ->from('counter')
            ->order('id DESC')
            ->toArray()
            ->cacheOn()
            ->first('counter', 'visited', array($sql1, 'members'), array($sql4, 'useronline'));
    }

    /**
     * get pages view
     *
     * @return array
     */
    public static function pageviews()
    {
        $select = array(
            Sql::MONTH('date', 'month'),
            Sql::YEAR('date', 'year'),
            Sql::SUM('pages_view', 'pages_view'),
            Sql::SUM('visited', 'visited')
        );
        $sql1 = static::createQuery()
            ->select($select)
            ->from('counter')
            ->groupBy('year', 'month')
            ->order('year DESC', 'month DESC')
            ->limit(12);
        return static::createQuery()
            ->select()
            ->from(array($sql1, 'A'))
            ->order('year', 'month')
            ->cacheOn()
            ->toArray()
            ->execute();
    }

    /**
     * get populate document
     *
     * @return array
     */
    public function popularpage()
    {
        return static::createQuery()
            ->select('D.topic', 'I.visited_today')
            ->from('index I')
            ->join('modules M', 'INNER', array(array('M.id', 'I.module_id'), array('M.owner', 'document')))
            ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id'), array('D.language', array(Language::name(), ''))))
            ->order('I.visited_today DESC', 'I.visited DESC')
            ->limit(12)
            ->cacheOn()
            ->toArray()
            ->execute();
    }
}
