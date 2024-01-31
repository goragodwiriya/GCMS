<?php
/**
 * @filesource modules/index/models/upgrade1400.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Upgrade1400;

/**
 * อัปเกรด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Index\Upgrade\Model
{
    /**
     * อัปเกรดเป็นเวอร์ชั่น 14.0.0
     *
     * @return object
     */
    public static function upgrade($db)
    {
        return (object) array(
            'content' => '<li class="correct">Upgrade to Version <b>14.0.0</b> complete.</li>',
            'version' => '14.0.0'
        );
    }
}
