<?php
/**
 * @filesource Widgets/Textlink/Views/Index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Textlink\Views;

/**
 * โมดูลสำหรับจัดการการตั้งค่าเริ่มต้น
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Gcms\View
{
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
        $styles = include ROOT_PATH.'Widgets/Textlink/styles.php';
        $patt = array('/{TITLE}/', '/{DESCRIPTION}/', '/{LOGO}/', '/{URL}/', '/{TARGET}/');
        // เรียงลำดับตาม last_preview
        $items = \Kotchasan\ArrayTool::sort($items, 'last_preview');
        // ใช้แบนเนอร์รายการแรก
        $banner = reset($items);
        // อัปเดตรายการว่าแสดงผลแล้ว
        \Widgets\Textlink\Models\Index::previewUpdate($banner['id']);
        // แสดงผล
        $replace = array();
        $replace[] = $banner['text'];
        $replace[] = $banner['description'];
        $replace[] = WEB_URL.DATA_FOLDER.'image/'.$banner['logo'];
        $replace[] = empty($banner['url']) ? '' : ' href="'.$banner['url'].'"';
        $replace[] = $banner['target'] == '_blank' ? ' target="_blank"' : '';
        return '<div class="widget_textlink '.$banner['name'].'">'.preg_replace($patt, $replace, $styles['banner']).'</div>';
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
        // แสดงผล
        $textlinks = array();
        foreach ($items as $banner) {
            $textlinks[] = $banner['template'];
        }
        return '<div class="widget_textlink '.$banner['name'].'">'.implode('', $textlinks).'</div>';
    }

    /**
     * Banner Slideshow
     *
     * @param array $items รายการ textlinks
     * @param string $bg true slideshow หรือ slideshowbg
     *
     * @return string
     */
    public static function slideshow($items, $bg)
    {
        $textlinks = array();
        foreach ($items as $item) {
            $row = '<figure class="figure">';
            if (empty($item['url'])) {
                $a = '';
            } else {
                $a = ' href="'.$item['url'].'"'.($item['target'] == '_blank' ? ' target="_blank"' : '').' title="'.$item['text'].'"';
            }
            $row .= '<a'.$a.'><img alt="'.$item['text'].'" class="nozoom" src="'.WEB_URL.DATA_FOLDER.'image/'.$item['logo'].'"></a>';
            if ($item['text'] != '') {
                $row .= '<figcaption><a'.$a.'><span>'.$item['text'].'</span></a></figcaption>';
            }
            $row .= '</figure>';
            $textlinks[] = $row;
        }
        // แสดงผล
        $id = uniqid();
        $content = '<div id="'.$id.'"><div class="'.$bg.'">'.implode("\n", $textlinks).'</div></div><script>new GBanner("'.$id.'"';
        if ($bg == 'slideshowbg') {
            $content .= ',{backgroundImage:true}';
        }
        $content .= ').playSlideShow();</script>';
        return $content;
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
        $styles = include ROOT_PATH.'Widgets/Textlink/styles.php';
        $patt = array('/{TITLE}/', '/{DESCRIPTION}/', '/{LOGO}/', '/{URL}/', '/{TARGET}/');
        $item = reset($items);
        if (isset($styles[$item['type']])) {
            $template = $styles[$item['type']];
            // แสดงผล
            $textlinks = array();
            foreach ($items as $banner) {
                $replace = array();
                $replace[] = $banner['text'];
                $replace[] = $banner['description'];
                $replace[] = WEB_URL.DATA_FOLDER.'image/'.$banner['logo'];
                $replace[] = empty($banner['url']) ? '' : ' href="'.$banner['url'].'"';
                $replace[] = $banner['target'] == '_blank' ? ' target="_blank"' : '';
                $textlinks[] = preg_replace($patt, $replace, $template);
            }
            return implode('', $textlinks);
        }
    }
}
