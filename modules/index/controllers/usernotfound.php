<?php
/**
 * @filesource modules/index/controllers/usernotfound.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Usernotfound;

/**
 * หน้าเพจ 404 (Page Not Found)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * 404 page not found
     */
    public static function execute()
    {
        echo preg_replace('/{HOSTNAME}/', self::$cfg->host_name, file_get_contents(ROOT_PATH.'themes/404.html'));
    }
}
