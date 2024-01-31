<?php
/**
 * @filesource modules/css/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Css\Index;

/**
 * Generate CSS file
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\KBase
{
    /**
     * สร้างไฟล์ CSS
     */
    public function index()
    {
        // session
        self::$request->initSession();
        // โหลด css หลัก
        $data = preg_replace('/url\(([\'"])?fonts\//isu', 'url(\\1'.WEB_URL.'skin/fonts/', file_get_contents(ROOT_PATH.'skin/fonts.css'));
        $data .= file_get_contents(ROOT_PATH.'skin/gcss.css');
        $data .= file_get_contents(ROOT_PATH.'skin/gcms.css');
        // frontend template
        $skin = 'skin/'.(empty($_SESSION['skin']) ? self::$cfg->skin : $_SESSION['skin']);
        // ไดเร็คทอรี่ template
        $dir = APP_PATH.$skin.'/';
        $data2 = file_get_contents($dir.'style.css');
        $data2 = preg_replace('/url\(([\'"])?(img|fonts)\//isu', 'url(\\1'.WEB_URL.$skin.'/\\2/', $data2);
        // css ของโมดูล
        $f = @opendir($dir);
        if ($f) {
            while (false !== ($text = readdir($f))) {
                if ($text != '.' && $text != '..') {
                    if (is_dir($dir.$text)) {
                        if (is_file($dir.$text.'/style.css')) {
                            $data2 .= preg_replace('/url\(img\//isu', 'url('.WEB_URL.$skin.'/'.$text.'/img/', file_get_contents($dir.$text.'/style.css'));
                        }
                    }
                }
            }
            closedir($f);
        }
        // โหลด css ของ Widgets
        $dir = ROOT_PATH.'Widgets/';
        $f = @opendir($dir);
        if ($f) {
            while (false !== ($text = readdir($f))) {
                if ($text != '.' && $text != '..') {
                    if (is_dir($dir.$text)) {
                        if (is_file($dir.$text.'/style.css')) {
                            $data2 .= preg_replace('/url\(img\//isu', 'url('.WEB_URL.'Widgets/'.$text.'/img/', file_get_contents($dir.$text.'/style.css'));
                        }
                    }
                }
            }
            closedir($f);
        }
        // status color
        foreach (self::$cfg->color_status as $key => $value) {
            $data2 .= '.status'.$key.'{color:'.$value.'}';
        }
        $bg = '';
        if (!empty(self::$cfg->bg_image) && is_file(ROOT_PATH.DATA_FOLDER.'image/'.self::$cfg->bg_image)) {
            $bg .= 'background-image:url('.WEB_URL.DATA_FOLDER.'image/'.self::$cfg->bg_image.');';
            $bg .= 'background-repeat:repeat;';
        }
        if (!empty(self::$cfg->bg_color)) {
            $bg .= 'background-color:'.self::$cfg->bg_color.';';
        }
        if ($bg != '') {
            $data2 .= 'body{'.$bg.'}';
        }
        // compress css
        $data = self::compress($data.$data2);
        // Response
        $response = new \Kotchasan\Http\Response();
        $response->withHeaders(array(
            'Content-type' => 'text/css; charset=utf-8',
            'Cache-Control' => 'max-age=31557600'
        ))
            ->withContent($data)
            ->send();
    }

    /**
     * @param $css
     */
    public static function compress($css)
    {
        return preg_replace(array('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '/[\s]{0,}([:;,>\{\}])[\s]{0,}/', '/[\r\n\t]/s', '/[\s]{2,}/s', '/;}/'), array('', '\\1', '', ' ', '}'), $css);
    }
}
