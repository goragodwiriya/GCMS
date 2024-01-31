<?php
/**
 * @filesource modules/index/views/dberror.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Dberror;

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
     * ข้อผิดพลาดการเชื่อมต่อฐานข้อมูล
     *
     * @return string
     */
    public function render(Request $request)
    {
        $content = array();
        if (defined('INSTALL')) {
            $content[] = '<h2>{TITLE}</h2>';
            $content[] = '<p class=warning>ไม่สามารถเชื่อมต่อกับฐานข้อมูลของคุณได้ในขณะนี้&nbsp;&nbsp;<a href="http://gcms.in.th/index.php?module=howto&id=39" target="_setup"><img src="modules/index/views/img/help.png" alt=help></a></p>';
            $content[] = '<p>อาจเป็นไปได้ว่า</p>';
            $content[] = '<ol>';
            $content[] = '<li>เซิร์ฟเวอร์ของฐานข้อมูลของคุณไม่สามารถใช้งานได้ในขณะนี้</li>';
            if ($request->post('newdb')->toInt() == 1) {
                $content[] = '<li>ไม่สามารถสร้างฐานข้อมูลได้ อาจเป็นเพราะคุณไม่มีสิทธิ์ ให้ลองเลือกใช้ฐานข้อมูลที่คุณมีอยู่ก่อนแล้ว</li>';
            }
            $content[] = '<li>ไม่มีฐานข้อมูลที่ต้องการติดตั้ง ให้ลองเลือกให้โปรแกรมสร้างฐานข้อมูลให้</li>';
            $content[] = '</ol>';
            $content[] = '<p>หากคุณไม่สามารถดำเนินการแก้ไขข้อผิดพลาดด้วยตัวของคุณเองได้ ให้ติดต่อผู้ดูแลระบบเพื่อขอข้อมูลที่ถูกต้อง</p>';
            $content[] = '<p><a href="index.php?step=3" class="button large pink">กลับไปลองใหม่</a></p>';
        }
        return (object) array(
            'title' => 'ความผิดพลาดในการเชื่อมต่อกับฐานข้อมูล',
            'content' => implode('', $content)
        );
    }
}
