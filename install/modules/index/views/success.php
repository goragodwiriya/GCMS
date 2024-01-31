<?php
/**
 * @filesource modules/index/views/success.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Success;

use Kotchasan\Http\Request;

/**
 * ติดตั้ง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ติดตั้งเรียบร้อยแล้ว
     *
     * @return string
     */
    public function render(Request $request)
    {
        $content = array();
        if (defined('INSTALL')) {
            $content[] = '<h2>{TITLE}</h2>';
            $content[] = '<p>คุณได้ทำการติดตั้ง GCMS เป็นที่เรียบร้อยแล้ว</p>';
            $content[] = '<p class=warning>เพื่อความปลอดภัย กรุณาลบโฟลเดอร์ <em>install/</em> ออกก่อนดำเนินการต่อ</p>';
            $content[] = '<p><a href="'.WEB_URL.'admin/index.php?module=system" class="button large admin">เข้าระบบผู้ดูแล</a></p>';
        }
        return (object) array(
            'title' => 'ติดตั้ง GCMS เวอร์ชั่น '.self::$cfg->version.' เรียบร้อย',
            'content' => implode('', $content),
        );
    }
}
