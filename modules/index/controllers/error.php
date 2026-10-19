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

use Kotchasan\Language;
use Kotchasan\Template;

/**
 * Error Controller ถ้าไม่สามารถทำรายการได้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Web\Controller
{
    /**
     * แสดงข้อผิดพลาด (เช่น 404 page not found)
     *
     * @param string $module  ชื่อโมดูลที่เรียก
     * @param string $message ข้อความที่จะแสดง ถ้าไม่กำหนดจะใช้ข้อความของระบบ
     *
     * @return object
     */
    public function init($module, $status = 404, $message = '')
    {
        $template = Template::create($module, '', '404');
        // A key of its own. The previous text was shared with the search view,
        // which passes it with a fallback of its own and depends on it having
        // no translation (modules/index/views/search.php) — translating it
        // there would have replaced "Search failed." with this sentence.
        $message = Language::get($message == '' ? 'The page you are looking for was not found.' : $message);
        $template->add([
            '/{TOPIC}/' => $message,
            '/{DETAIL}/' => $message
        ]);
        $topic = strip_tags($message);
        return (object) [
            'status' => $status,
            'topic' => $topic,
            'detail' => $template->render(),
            'description' => $topic,
            // <meta name="keywords"> — a sentence belongs in the description,
            // not here, and an error page has no keywords worth indexing.
            'keywords' => '',
            'module' => $module
        ];
    }
}
