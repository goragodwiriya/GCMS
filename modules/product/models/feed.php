<?php
/**
 * @filesource modules/product/models/feed.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Feed;

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
        $where = [
            ['P.module_id', (int) $index->module_id],
            ['P.published', 1]
        ];
        return $model->db()->createQuery()
            ->select('P.id', 'D.topic', 'P.alias', 'D.description', 'P.picture', 'P.last_update')
            ->from('product P')
            ->join('product_detail D', 'INNER', [['D.id', 'P.id'], ['D.language', [Language::name(), '']]])
            ->where($where)
            ->order(($request->get('rnd')->exists() ? 'RAND()' : 'P.last_update DESC'))
            ->limit($count)
            ->cacheOn()
            ->execute();
    }
}
