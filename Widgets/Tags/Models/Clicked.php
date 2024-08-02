<?php
/**
 * @filesource Widgets/Tags/Controllers/Clicked.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Tags\Models;

use Kotchasan\Http\Request;

/**
 * รับค่าจากการคลิก Tag
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Clicked extends \Kotchasan\Model
{
    /**
     * @param Request $request
     */
    public static function submit(Request $request)
    {
        if ($request->isAjax() && $request->isReferer()) {
            if (preg_match('/tags\-([0-9]+)/', $request->post('id')->toString(), $match)) {
                \Kotchasan\Model::createQuery()->update('tags')->set('`count`=`count`+1')->where((int) $match[1])->execute();
            }
        }
    }
}
