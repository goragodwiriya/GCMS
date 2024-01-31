<?php
/**
 * @filesource modules/index/views/maintenance.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Maintenance;

use Kotchasan\Language;

/**
 * intro page
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ส่งออกเป็น HTML
     *
     * @param string|null $template HTML Template ถ้าไม่กำหนด (null) จะใช้ index.html
     */
    public function renderHTML($template = null)
    {
        // maintenance detail
        $template = ROOT_PATH.DATA_FOLDER.'maintenance.'.Language::name().'.php';
        if (is_file($template)) {
            $template = trim(preg_replace('/<\?php exit([\(\);])?\?>/', '', file_get_contents($template)));
        } else {
            $template = '<p style="padding: 20px; text-align: center; font-weight: bold;">Website Temporarily Closed for Maintenance, Please try again in a few minutes.<br>ปิดปรับปรุงเว็บไซต์ชั่วคราวเพื่อบำรุงรักษา กรุณาลองใหม่ในอีกสักครู่</p>';
        }
        parent::setContents(array(
            '/{TITLE}/' => self::$cfg->web_title,
            '/{CONTENT}/' => $template
        ));
        return parent::renderHTML(file_get_contents(ROOT_PATH.'skin/empty.html'));
    }
}
