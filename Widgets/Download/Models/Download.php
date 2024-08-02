<?php
/**
 * @filesource Widgets/Download/Models/Download.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Download\Models;

/**
 * ไฟล์ดาวน์โหลด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Download extends \Kotchasan\Model
{
    /**
     * ไฟล์ดาวน์โหลด
     *
     * @param int $id
     *
     * @return array
     */
    public static function get($id)
    {
        // query
        $model = new static;
        return $model->db()->createQuery()->from('download')->where((int) $id)->cacheOn()->toArray()->first();
    }
}
