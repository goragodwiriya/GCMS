<?php
/**
 * @filesource gcmss/installer.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcmss\Installer;

/**
 * เว็บไซต์โรงเรียนหรือ อบต
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param $db
     */
    public static function import($db)
    {
        self::$cfg->skin = 'gts';
    }
}
