<?php
/**
 * @filesource modules/document/models/autocomplete.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Autocomplete;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * ค้นหาสมาชิก สำหรับ autocomplete
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค้นหา relate สำหรับ autocomplete
     * คืนค่าเป็น JSON
     *
     * @param Request $request
     */
    public function findRelate(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            $search = $request->post('search')->topic();
            $language = $request->post('language')->filter('a-z');
            $module_id = $request->post('module_id')->toInt();
            if ($search != '') {
                $result = $this->db()->createQuery()
                    ->select('relate')
                    ->from('index_detail')
                    ->where(array(
                        array('module_id', $module_id),
                        array('language', array('', $language)),
                        array('relate', 'LIKE', '%'.$search.'%')
                    ))
                    ->groupBy('relate')
                    ->limit($request->post('count', 20)->toInt())
                    ->toArray()
                    ->cacheOn()
                    ->execute();
                // คืนค่า JSON
                echo json_encode($result);
            }
        }
    }

    /**
     * ค้นหา tag สำหรับ autocomplete
     * คืนค่าเป็น JSON
     *
     * @param Request $request
     */
    public function findTag(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            try {
                $search = $request->post('search')->topic();
                $result = $this->db()->createQuery()
                    ->select('tag')
                    ->from('tags')
                    ->where(array(
                        array('tag', 'LIKE', '%'.$search.'%'),
                        array('tag', '!=', $search)
                    ))
                    ->order('tag')
                    ->limit($request->post('count', 20)->toInt())
                    ->toArray()
                    ->cacheOn()
                    ->execute();
                // คืนค่า JSON
                if ($search != '') {
                    $result = array(array('tag' => $search)) + $result;
                }
                echo json_encode($result);
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }
}
