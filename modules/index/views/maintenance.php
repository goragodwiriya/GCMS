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

/**
 * intro page
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * ส่งออกเป็น HTML
     *
     * @param string|null $template HTML Template ถ้าไม่กำหนด (null) จะใช้ index.html
     */
    public function renderHTML($template = null)
    {
        // maintenance detail
        $template = ROOT_PATH.DATA_FOLDER.'maintenance.'.LANGUAGE.'.html';
        if (is_file($template)) {
            $template = trim(preg_replace('/<\?php exit([\(\);])?\?>/', '', file_get_contents($template)));
        } else {
            $template = '<p style="padding: 20px; text-align: center; font-weight: bold;">Website Temporarily Closed for Maintenance, Please try again in a few minutes.<br>ปิดปรับปรุงเว็บไซต์ชั่วคราวเพื่อบำรุงรักษา กรุณาลองใหม่ในอีกสักครู่</p>';
        }
        $favicon = is_file(ROOT_PATH.DATA_FOLDER.'image/favicon.ico') ? WEB_URL.DATA_FOLDER.'image/favicon.ico' : WEB_URL.'favicon.ico';
        parent::setContents([
            '/{TITLE}/' => self::$cfg->web_title,
            '/{CONTENT}/' => $template,
            '/{FAVICON}/' => $favicon,
            '/{LANGUAGE}/' => LANGUAGE
        ]);
        return parent::renderHTML(file_get_contents(ROOT_PATH.'themes/empty.html'));
    }
}
