<?php
/**
 * @filesource widgets/textlinks/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlinks\Views;

use Kotchasan\Text;

/**
 * Textlinks Widget — Frontend Renderer
 *
 * แสดงผลลิงค์ทั้งกลุ่มพร้อม wrapper ของกลุ่ม เพื่อให้แทรกลงใน section ใดก็ได้
 * โดยไม่ต้องให้ theme เขียน <ul> หรือ <div> ครอบเอง
 *
 *   เมนูข้อความ : <nav class="widget_textlink sidemenu textlink-{name}"><ul><li><a>..
 *   เมนูรูปภาพ  : <div class="widget_textlink textlinks-footer textlink-{name}"><a><img>..
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Web\View
{
    /**
     * Escape a URL for an href attribute, rejecting dangerous schemes.
     *
     * @param string $url
     *
     * @return string
     */
    private static function escUrl($url)
    {
        $url = (string) $url;
        if ($url === '' || preg_match('/^\s*(javascript|data|vbscript)\s*:/i', $url)) {
            return '';
        }
        return Text::htmlspecialchars($url);
    }

    /**
     * Build the HTML-escaped replacement values for one link.
     * DB fields (text/description/url/logo) are escaped to prevent stored XSS.
     *
     * @param object $item
     *
     * @return array matching the {TITLE},{DESCRIPTION},{LOGO},{URL},{TARGET} pattern
     */
    private static function itemReplace($item)
    {
        $href = self::escUrl($item->url ?? '');
        return [
            Text::htmlspecialchars((string) $item->text),
            Text::htmlspecialchars((string) $item->description),
            Text::htmlspecialchars(WEB_URL.DATA_FOLDER.'image/'.$item->logo),
            $href === '' ? '' : ' href="'.$href.'"',
            $item->target == '_blank' ? ' target="_blank" rel="noopener noreferrer"' : ''
        ];
    }

    /**
     * แสดงผลลิงค์ทั้งกลุ่ม
     *
     * ชนิดของกลุ่มดูจากรายการแรก ถ้าเป็นรูปภาพจะใช้ wrapper แบบแถวโลโก้
     * รายการที่ชนิดไม่ตรงกับกลุ่มยังแสดงได้ตามปกติ (ใช้ template ของตัวเอง)
     *
     * @param string $name   ชื่อกลุ่ม
     * @param array  $items  รายการลิงค์จาก Models\Index::getItems()
     * @param string $layout slideshow | banner (ว่าง = เมนูตามชนิดของลิงค์)
     *
     * @return string
     */
    public static function render($name, $items, $layout = '')
    {
        if (empty($items)) {
            return '';
        }
        if ($layout === 'slideshow' || $layout === 'banner') {
            return self::imageLayout($name, $items, $layout);
        }

        $styles = include ROOT_PATH.'widgets/textlinks/styles.php';
        $patt = ['{TITLE}', '{DESCRIPTION}', '{LOGO}', '{URL}', '{TARGET}'];

        $className = ['widget_textlink', 'textlink-'.Text::htmlspecialchars($name), 'sidemenu'];

        $links = [];
        foreach ($items as $item) {
            if ($item->type === 'image') {
                unset($className[2]);
                if (empty($item->logo)) {
                    // รูปภาพที่ยังไม่ได้อัปโหลด ไม่มีอะไรให้แสดง
                    continue;
                }
            }
            $link = str_replace($patt, self::itemReplace($item), $styles[$item->type]);
            $links[] = '<li>'.$link.'</li>';
        }

        if (empty($links)) {
            return '';
        }

        return '<nav class="'.implode(' ', $className).'"><ul>'.implode('', $links).'</ul></nav>';
    }

    /**
     * แสดงรูปของกลุ่มเป็นสไลด์ (layout=slideshow) หรือแบนเนอร์สุ่มทีละรูป (layout=banner)
     *
     * ข้อมูลยังเป็นเมนูรูปภาพธรรมดา (type = image) ผู้ดูแลจัดการได้จากหน้าเดิม
     * layout เป็นแค่วิธีแสดงผล กำหนดที่ธีม เหมือน layout ของ widget document
     *
     * @param string $name   ชื่อกลุ่ม
     * @param array  $items  รายการลิงค์
     * @param string $layout slideshow | banner
     *
     * @return string
     */
    private static function imageLayout($name, $items, $layout)
    {
        $slides = [];
        foreach ($items as $item) {
            if ($item->type !== 'image' || empty($item->logo)) {
                continue;
            }
            list($title, , $logo, $href, $target) = self::itemReplace($item);
            $img = '<img alt="'.$title.'" src="'.$logo.'" loading="lazy">';
            $slides[] = $href === '' ? $img : '<a title="'.$title.'"'.$href.$target.'>'.$img.'</a>';
        }
        if (empty($slides)) {
            return '';
        }
        $className = 'widget_textlink textlink-'.Text::htmlspecialchars($name).' textlink-'.$layout;
        if ($layout === 'banner') {
            return '<div class="'.$className.'">'.$slides[array_rand($slides)].'</div>';
        }
        $html = '<div class="'.$className.' gallery-slideshow" data-slideshow data-height="auto" data-show-expand="false" data-glow="false">';
        foreach ($slides as $i => $slide) {
            $html .= '<div class="now-slide" data-index="'.$i.'">'.$slide.'</div>';
        }

        return $html.'</div>';
    }
}
