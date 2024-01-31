<?php
/**
 * @filesource modules/document/models/stories.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Stories;

use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * ลิสต์รายการ บทความ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ลิสต์รายการ บทความ
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function stories(Request $request, $index)
    {
        if (isset($index->module_id)) {
            $where = array(array('D.module_id', (int) $index->module_id));
            if (!empty($index->category_id)) {
                $where[] = array('I.category_id', is_array($index->category_id) ? $index->category_id : (int) $index->category_id);
            }
            $where[] = array('D.language', array(Language::name(), ''));
            // คืนค่า
            return self::execute($request, $index, $where);
        }
        return null;
    }

    /**
     * ลิสต์รายการบทความตาม tag
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function tags(Request $request, $index)
    {
        // query
        $where = array(
            array('D.language', array(Language::name(), '')),
            array('D.relate', 'LIKE', '%'.$index->tag.'%')
        );
        // คืนค่า
        return self::execute($request, $index, $where);
    }

    /**
     * ลิสต์รายการบทความตามวันที่
     *
     * @param Request $request
     * @param object  $index
     *
     * @return object
     */
    public static function calendar(Request $request, $index)
    {
        if (preg_match('/^([0-3]?[0-9])[\-|\s]([0-1]?[0-9])[\-|\s]([0-9]{4,4})$/', $index->alias, $ds)) {
            // วันที่
            $selday = mktime(0, 0, 0, $ds[2], $ds[1], (int) $ds[3]);
            // แปลงวันที่จากปฏิทินเป็นวันที่ของ SQL
            $index->d = date('Y-m-d', $selday);
            // query
            $where = array(
                array('D.language', array(Language::name(), '')),
                array('I.create_date', '>=', $selday),
                array('I.create_date', '<=', $selday + 86400)
            );
            // คืนค่า
            return self::execute($request, $index, $where);
        }
        return null;
    }

    /**
     * Query
     *
     * @param Request $request
     * @param object  $index
     * @param array   $where
     *
     * @return object
     */
    private static function execute(Request $request, $index, $where)
    {
        // Model
        $model = new static;
        // query
        $query = $model->db()->createQuery()
            ->from('index_detail D')
            ->join('modules M', 'INNER', array('M.id', 'D.module_id'))
            ->join('index I', 'INNER', array(array('I.id', 'D.id'), array('I.module_id', 'D.module_id'), array('I.index', 0), array('I.published', 1), array('I.published_date', '<=', date('Y-m-d'))))
            ->where($where);
        // จำนวน
        $index->total = $query->cacheOn()->count();
        // ข้อมูลแบ่งหน้า
        if (empty($index->rows)) {
            $index->rows = 20;
        }
        if (empty($index->cols)) {
            $index->cols = 1;
        }
        $list_per_page = $index->rows * $index->cols;
        $index->page = $request->request('page')->toInt();
        $index->totalpage = ceil($index->total / $list_per_page);
        $index->page = max(1, ($index->page > $index->totalpage ? $index->totalpage : $index->page));
        $index->start = $list_per_page * ($index->page - 1);
        // query (sort, split)
        $select = array(
            'I.id',
            'D.topic',
            'I.alias',
            'D.description',
            'I.last_update',
            'I.create_date',
            'I.comment_date',
            'I.visited',
            'I.comments',
            'I.picture',
            'I.member_id',
            'U.status',
            'U.displayname',
            'U.email',
            'M.module'
        );
        // เรียงลำดับ
        $sorts = array(
            array('I.last_update DESC', 'I.id DESC'),
            array('I.create_date DESC', 'I.id DESC'),
            array('I.published_date DESC', 'I.last_update DESC'),
            array('I.id DESC')
        );
        if (empty($index->sort) || !isset($sorts[$index->sort])) {
            $index->sort = 0;
        }
        $query->select($select)
            ->join('user U', 'LEFT', array('U.id', 'I.member_id'))
            ->order(isset($sorts[$index->sort]) ? $sorts[$index->sort] : $sorts[0])
            ->limit($list_per_page, $index->start);
        $index->items = $query->cacheOn()->execute();
        // คืนค่า
        return $index;
    }
}
