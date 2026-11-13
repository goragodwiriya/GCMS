<?php
/**
 * @filesource widgets/textlinks/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Textlinks\Controllers;

/**
 * Textlinks Widget — Frontend Controller
 *
 * แทรกลง section ได้เหมือน widget อื่นๆ โดยเลือกกลุ่มที่จะแสดงจากพารามิเตอร์ name
 *
 *   {WIDGET_TEXTLINKS name=footer}  หรือ  {WIDGET_TEXTLINKS_footer}
 *
 * ตั้งแต่รุ่นนี้ widget เป็นผู้สร้าง wrapper ของกลุ่มเอง (<nav><ul> หรือ
 * <div class="textlinks-footer">) theme จึงไม่ต้องเขียนครอบอีก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Display Widget
     *
     * @param array $query_string name (หรือ module สำหรับ {WIDGET_TEXTLINKS_xxx}),
     *                            layout = slideshow | banner (ไม่ระบุ = เมนูตามชนิดของลิงค์)
     *                            เช่น {WIDGET_TEXTLINKS name=slideshow;layout=slideshow}
     *
     * @return string
     */
    public function get($query_string)
    {
        $name = isset($query_string['name']) ? $query_string['name'] : (isset($query_string['module']) ? $query_string['module'] : '');
        $name = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $name));

        if ($name === '') {
            return '';
        }
        $layout = isset($query_string['layout']) ? strtolower(trim((string) $query_string['layout'])) : '';

        return \Widgets\Textlinks\Views\Index::render($name, \Widgets\Textlinks\Models\Index::getItems($name), $layout);
    }
}
