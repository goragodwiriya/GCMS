<?php
/**
 * @filesource modules/board/controllers/sitemap.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Board\Sitemap;

use Web\Gcms;

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
     * @param array  $ids     แอเรย์ของ module_id
     * @param array  $modules แอเรย์ของ module ที่ติดตั้งแล้ว
     * @param string $date    วันที่วันนี้
     *
     * @return array
     */
    public function init($ids, $modules, $date)
    {
        $result = [];
        foreach (\Board\Sitemap\Model::getStories($ids) as $item) {
            $module = $modules[$item->module_id];
            $lastModified = empty($item->comment_date) ? $item->updated_at : $item->comment_date;
            $timestamp = strtotime((string) ($lastModified ?: $date));
            $result[] = (object) [
                'url' => Gcms::createUrl($module, '', 0, $item->id),
                'date' => date('Y-m-d', $timestamp ?: strtotime((string) $date))
            ];
        }
        return $result;
    }
}
