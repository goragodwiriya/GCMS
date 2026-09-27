<?php
/**
 * @filesource modules/index/views/intro.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Intro;

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
        // intro detail
        $template = ROOT_PATH.DATA_FOLDER.'intro.'.LANGUAGE.'.html';
        if (is_file($template)) {
            $template = trim(preg_replace(['/<\?php exit([\(\);])?\?>/', '/&\#x007B;/', '/&\#x007D;/'], ['', '{', '}'], file_get_contents($template)));
        } else {
            $template = '<p style="padding: 20px; text-align: center; font-weight: bold;"><a href="index.php">Welcome<br>ยินดีต้อนรับ</a></p>';
        }
        $favicon = \Web\Gcms::favicon();
        parent::setContents([
            '/{TITLE}/' => self::$cfg->web_title,
            '/{CONTENT}/' => $template,
            '/{FAVICON}/' => $favicon['url'],
            '/{FAVICON_TYPE}/' => $favicon['type'],
            '/{LANGUAGE}/' => LANGUAGE
        ]);
        return parent::renderHTML(file_get_contents(ROOT_PATH.'themes/empty.html'));
    }
}
