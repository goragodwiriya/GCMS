<?php
/**
 * @filesource widgets/facebook/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Widgets\Facebook\Controllers;

/**
 * Main Controller for displaying the Widget
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\KBase
{
    /**
     * Display Widget
     *
     * @param array $query_string Data sent by calling the widget
     *
     * @return string
     */
    public function get($query_string)
    {
        if (empty(self::$cfg->facebook) || empty(self::$cfg->facebook['user'])) {
            return '';
        }
        $facebook = self::$cfg->facebook;
        $params = [
            'href' => 'https://www.facebook.com/'.$facebook['user'],
            'tabs' => 'timeline',
            'width' => $facebook['width'],
            'height' => $facebook['height'],
            'show_facepile' => $facebook['show_facepile'] ? 'true' : 'false',
            'hide_cover' => $facebook['cover_image'] ? 'false' : 'true',
            'small_header' => $facebook['small_header'] ? 'true' : 'false',
            'adapt_container_width' => 'true'
        ];

        $iframe_url = 'https://www.facebook.com/plugins/page.php?'.http_build_query($params);
        return '<iframe src="'.$iframe_url.'" width="'.$facebook['width'].'" height="'.$facebook['height'].'" style="border:none;overflow:hidden;max-width:100%;" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture;" loading="lazy"></iframe>';
    }
}
