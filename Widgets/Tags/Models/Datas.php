<?php
/**
 * @filesource Widgets/Tags/Controllers/Datas.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Models;

use Kotchasan\Http\Request;

/**
 * อ่านข้อมูล tags
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Datas
{
    /**
     * อ่านข้อมูลที่ tags
     *
     * @param Request $request
     *
     * @return JSON
     */
    public static function execute(Request $request)
    {
        if ($request->isAjax() && $request->isReferer()) {
            $query = \Kotchasan\Model::createQuery()
                ->select()
                ->from('tags')
                ->cacheOn();
            $result = array();
            foreach ($query->execute() as $item) {
                $result[$item->id] = array(
                    'tag' => $item->tag,
                    'count' => $item->count
                );
            }
            // คืนค่า JSON
            echo json_encode($result);
        }
    }
}
