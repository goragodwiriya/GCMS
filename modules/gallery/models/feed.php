<?php
/**
 * @filesource modules/gallery/models/feed.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Feed;

use Kotchasan\Http\Request;

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
    public static function getAlbums(Request $request, $index, $count, $today)
    {
        if (defined('MAIN_INIT')) {
            // ค่าที่ส่งมา
            $model = new static;
            $where = array(
                array('G.module_id', (int) $index->module_id)
            );
            $aid = $request->get('album')->toInt();
            if ($aid == -1) {
                $where[] = array('G.count', 0);
            } elseif ($aid > 0) {
                $where[] = array('G.album_id', $aid);
            }
            return $model->db()->createQuery()
                ->select('A.id', 'A.topic', 'A.detail', 'G.image', 'A.last_update')
                ->from('gallery G')
                ->join('gallery_album A', 'INNER', array(array('A.id', 'G.album_id'), array('A.module_id', 'G.module_id')))
                ->where($where)
                ->limit($count)
                ->order(($request->get('rnd')->exists() ? 'RAND()' : 'G.id DESC'))
                ->cacheOn()
                ->execute();
        }
        // เรียก method โดยตรง
        new \Kotchasan\Http\NotFound('Do not call method directly');
    }
}
