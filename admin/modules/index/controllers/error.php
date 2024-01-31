<?php
/**
 * @filesource modules/index/controllers/error.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Error;

use Kotchasan\Template;

/**
 * Error Controller ถ้าไม่สามารถทำรายการได้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แสดงหน้า 404.html
     *
     * @return string
     */
    public function render()
    {
        // คืนค่า 404.html
        return Template::create('', '', '404')->render();
    }

    /**
     * แสดงหน้า 404.html (static)
     *
     * @return string
     */
    public static function page404()
    {
        $obj = new static;
        return $obj->render();
    }
}
