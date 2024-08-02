<?php
/**
 * @filesource modules/index/views/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Sitemap;

/**
 * register, forgot page
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * สร้างรายการ sitemap
     *
     * @param string $url
     * @param string $date
     *
     * @return string
     */
    public function render($url, $date)
    {
        return '<url><loc>'.$url.'</loc><lastmod>'.$date.'</lastmod><changefreq>daily</changefreq><priority>0.5</priority></url>';
    }
}
