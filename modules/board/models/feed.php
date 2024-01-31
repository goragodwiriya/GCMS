<?php
/**
 * @filesource modules/board/models/feed.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Feed;

use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * RSS Feed
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * RSS Feed
     *
     * @param Request $request
     * @param object  $index   ข้อมูลโมดูล
     * @param int     $count   จำนวนที่ต้องการ
     *
     * @return array
     */
    public static function getStories(Request $request, $index, $count)
    {
        $where = array(
            array('module_id', (int) $index->module_id)
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
        return \Kotchasan\Model::createQuery()
            ->select('id', 'topic', 'detail', 'picture', Sql::create('(CASE WHEN `comment_date`=0 THEN `last_update` ELSE `comment_date` END) AS `last_update`'))
            ->from('board_q')
            ->where($where)
            ->limit($count)
            ->order(($request->get('rnd')->exists() ? 'RAND()' : 'last_update DESC'))
            ->cacheOn()
            ->execute();
    }
}
