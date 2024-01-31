<?php
/**
 * @filesource modules/index/models/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Detail;

/**
 * โมเดลสำหรับแสดงรายการหน้าเว็บไซต์ที่สร้างแล้ว (pages.php)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Orm\Field
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'index_detail D';
}
