<?php
/**
 * @filesource Gcms/Amp.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

/**
 * View base class สำหรับ GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Amp extends \Gcms\Baseview
{
    /**
     * ouput เป็น HTML
     *
     * @param string|null $template HTML Template ถ้าไม่กำหนด (null) จะใช้ index.html
     *
     * @return string
     */
    public function renderHTML($template = null)
    {
        // เนื้อหา
        parent::setContents(array(
            /* AMP CSS */
            '/{CSS}/' => \Css\Index\View::compress(file_get_contents(ROOT_PATH.'skin/'.self::$cfg->skin.'/amp.css')),
            // widgets
            '/{WIDGET_([A-Z]+)([_\s]+([^}]+))?}/e' => '\Gcms\View::getWidgets(array(1=>"$1",3=>"$3"))',
            /* ภาษา */
            '/{LNG_([^}]+)}/e' => '\Kotchasan\Language::parse(array(1=>"$1"))',
            /* ภาษาที่ใช้งานอยู่ */
            '/{LANGUAGE}/' => \Kotchasan\Language::name()
        ));
        // JSON-LD
        if (!empty($this->jsonld)) {
            $this->metas['JsonLd'] = '<script type="application/ld+json">'.json_encode($this->jsonld, JSON_UNESCAPED_SLASHES).'</script>';
        }
        return preg_replace_callback('/<(iframe|img)([^>]+)>(<\/\\1>)?/is', function ($matchs) {
            // parse attribute
            $attributes = array();
            if (preg_match_all('/(\\w+)\s*=\\s*("[^"]*"|\'[^\']*\'|[^"\'\\s>]*)/', $matchs[2], $props, PREG_SET_ORDER)) {
                foreach ($props as $prop) {
                    if (($prop[2][0] == '"' || $prop[2][0] == "'") && $prop[2][0] == $prop[2][strlen($prop[2]) - 1]) {
                        $prop[2] = substr($prop[2], 1, -1);
                    }
                    $attributes[strtolower($prop[1])] = $prop[2];
                }
                $tag = strtolower($matchs[1]);
                if (isset($attributes['style']) && preg_match_all('/(width|height)[:\s]+([0-9]+)(px|em|\%|)(;|)/', $attributes['style'], $props, PREG_SET_ORDER)) {
                    foreach ($props as $item) {
                        if ($item[3] == 'px' || $item[3] == '') {
                            $attributes[$item[1]] = $item[2];
                        }
                    }
                }
                if ($tag == 'img' && isset($attributes['src'])) {
                    if (!isset($attributes['width'])) {
                        $img = str_replace(WEB_URL, ROOT_PATH, $attributes['src']);
                        if (is_file($img)) {
                            $size = @getimagesize($img);
                            if ($size) {
                                $attributes['width'] = $size[0];
                                $attributes['height'] = $size[1];
                            }
                        }
                    }
                    $attributes['layout'] = 'responsive';
                } elseif ($tag == 'iframe') {
                    $attributes['layout'] = 'responsive';
                    $attributes['sandbox'] = 'allow-scripts allow-same-origin';
                    $attributes['resizable'] = 'resizable';
                    $attributes['width'] = 300;
                    $attributes['height'] = empty($attributes['height']) ? 300 : (int) $attributes['height'];
                    unset($attributes['frameborder']);
                } elseif ($tag == 'style') {
                    unset($attributes);
                    $tag = 'custom';
                }
                unset($attributes['style']);
                $prop = array();
                foreach ($attributes as $key => $value) {
                    if ($key == $value) {
                        $prop[$key] = $key;
                    } else {
                        $prop[$key] = $key.'="'.$value.'"';
                    }
                }
                $prop = empty($prop) ? '' : ' '.implode(' ', $prop);
                return '<amp-'.$tag.$prop.'></amp-'.$tag.'>';
            }
        }, parent::renderHTML(\Kotchasan\Template::load('', '', 'amp')));
    }
}
