<?php
/**
 * @filesource modules/gallery/controllers/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Sitemap;

/**
 * sitemap.xml
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * แสดงผล sitemap.xml
     *
     * @param string $cdate วันที่แก้ไขล่าสุด
     *
     * @return array
     */
    public function init($cdate)
    {
        $result = [];
        foreach (\Gallery\Lists\Model::create()->sitemap() as $item) {
            $result[] = (object) [
                'url' => \Gallery\Index\Controller::url('gallery', $item->id),
                'date' => date('Y-m-d', strtotime($item->created_at))
            ];
        }
        return $result;
    }
}
