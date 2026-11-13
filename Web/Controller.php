<?php
/**
 * @filesource Web/Controller.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Web;

/**
 * Controller base class for GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * ข้อความไตเติลบาร์
     *
     * @var string
     */
    protected $title;

    /**
     * init Class
     */
    public function __construct()
    {
        // ค่าเริ่มต้นของ Controller
        $this->title = strip_tags(self::$cfg->web_title);
    }

    /**
     * ข้อความ title bar
     *
     * @return string
     */
    public function title()
    {
        return $this->title;
    }
}
