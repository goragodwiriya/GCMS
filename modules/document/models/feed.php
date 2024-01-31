<?php
/**
 * @filesource modules/document/models/feed.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Feed;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * RSS Feed
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * RSS Feed
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     * @param int     $count   จำนวนที่ต้องการ
     * @param string  $today   วันที่วันนี้ รูปแบบ Y-m-d
     *
     * @return array
     */
    public static function getStories(Request $request, $index, $count, $today)
    {
        $model = new static;
        $where = array(
            array('I.module_id', (int) $index->module_id),
            array('I.index', 0),
            array('I.published', 1),
            array('I.published_date', '<=', $today)
        );
        if (preg_match('/^([0-9,]+)$/', $request->get('cat')->toString(), $cat)) {
            $where[] = array('category_id', explode(',', $cat[0]));
        }
        $user = $request->get('user')->toInt();
        if ($user > 0) {
            $where[] = array('member_id', $user);
        }
        if ($request->get('album')->exists()) {
            $where[] = array('picture', '!=', '');
        }
        return $model->db()->createQuery()
            ->select('I.id', 'D.topic', 'I.alias', 'D.description', 'I.picture', 'I.create_date')
            ->from('index I')
            ->join('index_detail D', 'INNER', array(array('D.id', 'I.id'), array('D.module_id', 'I.module_id'), array('D.language', array(Language::name(), ''))))
            ->where($where)
            ->limit($count)
            ->order(($request->get('rnd')->exists() ? 'RAND()' : 'I.create_date DESC'))
            ->cacheOn()
            ->execute();
    }
}
