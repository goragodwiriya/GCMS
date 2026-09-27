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

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
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
     * @return string
     */
    private static function escUrl($url)
    {
        $url = (string) $url;
        if ($url === '' || preg_match('/^\s*(javascript|data|vbscript)\s*:/i', $url)) {
            return '';
        }
        return \Kotchasan\Text::htmlspecialchars($url);
    }

    /**
     * Build the HTML-escaped replacement values for a banner row.
     * DB fields (text/description/url/logo) are escaped to prevent stored XSS.
     *
     * @param object $banner
     * @return array matching the {TITLE},{DESCRIPTION},{LOGO},{URL},{TARGET} pattern
     */
    private static function bannerReplace($banner)
    {
        $href = self::escUrl($banner->url ?? '');
        return [
            \Kotchasan\Text::htmlspecialchars((string) $banner->text),
            \Kotchasan\Text::htmlspecialchars((string) $banner->description),
            \Kotchasan\Text::htmlspecialchars(WEB_URL.DATA_FOLDER.'textlink/'.$banner->logo),
            $href === '' ? '' : ' href="'.$href.'"',
            $banner->target == '_blank' ? ' target="_blank"' : ''
        ];
    }

    /**
     * แสดงแบนเนอร์ทีละรูป หมุนวน
     *
     * @param array $items รายการ textlinks
     *
     * @return string
     */
    public static function banner($items)
    {
        // template
        $styles = include ROOT_PATH.'widgets/textlinks/styles.php';
        $patt = ['{TITLE}', '{DESCRIPTION}', '{LOGO}', '{URL}', '{TARGET}'];
        // เรียงลำดับตาม last_preview
        $items = \Kotchasan\ArrayTool::sort($items, 'last_preview');
        // ใช้แบนเนอร์รายการแรก
        $banner = reset($items);
        // อัปเดตรายการว่าแสดงผลแล้ว
        \Widgets\Textlinks\Models\Index::previewUpdate($banner->id);
        // แสดงผล
        $replace = self::bannerReplace($banner);
        return '<div class="widget_textlink textlink-'.\Kotchasan\Text::htmlspecialchars((string) $banner->name).'">'.str_replace($patt, $replace, $styles['banner']).'</div>';
    }

    /**
     * แสดง ADS ที่กำหนดเองเช่น Adsense
     *
     * @param array $items รายการ textlinks
     *
     * @return string
     */
    public static function custom($items)
    {
        $patt = ['{TITLE}', '{DESCRIPTION}', '{LOGO}', '{URL}', '{TARGET}'];
        // แสดงผล
        $textlinks = [];
        foreach ($items as $banner) {
            $replace = self::bannerReplace($banner);
            $textlinks[] = str_replace($patt, $replace, $banner->template);
        }

        return '<div class="widget_textlink  textlink-'.\Kotchasan\Text::htmlspecialchars((string) $banner->name).'">'.implode('', $textlinks).'</div>';
    }

    /**
     * Banner Slideshow
     *
     * @param array $items รายการ textlinks
     *
     * @return string
     */
    public static function slideshow($items)
    {
        $textlinks = '';
        $textlinks .= '<div class="gallery-slideshow" id="'.uniqid().'" data-slideshow data-height="auto" data-show-expand="false" data-glow="false">';
        $i = 0;
        foreach ($items as $item) {
            $imgUrl = \Kotchasan\Text::htmlspecialchars(WEB_URL.DATA_FOLDER.'textlink/'.$item->logo);
            $textlinks .= '<div class="now-slide" data-index="'.$i.'"><img src="'.$imgUrl.'" alt="'.\Kotchasan\Text::htmlspecialchars((string) $item->text).'" loading="lazy"></div>';
            $i++;
        }
        $textlinks .= '</div>';
        return $textlinks;
    }

    /**
     * รูปแบบอื่นๆ
     *
     * @param array $items รายการ textlinks
     *
     * @return string
     */
    public static function template($items)
    {
        // template
        $styles = include ROOT_PATH.'widgets/textlinks/styles.php';
        $patt = ['{TITLE}', '{DESCRIPTION}', '{LOGO}', '{URL}', '{TARGET}'];
        $item = reset($items);
        if (isset($styles[$item->type])) {
            $template = $styles[$item->type];
            // แสดงผล
            $textlinks = [];
            foreach ($items as $banner) {
                $replace = self::bannerReplace($banner);
                $textlinks[] = str_replace($patt, $replace, $template);
            }
            return implode('', $textlinks);
        }
    }
}
