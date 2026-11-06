<?php
/**
 * @filesource modules/product/controllers/sitemap.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Sitemap;

/**
 * sitemap.xml — the published products of every product module
 * (called by Index\Sitemap\Controller for installed owners)
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
        foreach (\Product\Sitemap\Model::getProducts($ids) as $item) {
            $result[] = (object) [
                'url' => \Product\Index\Controller::url($modules[$item->module_id], $item->alias, $item->id),
                'date' => $item->updated_at ? date('Y-m-d', strtotime($item->updated_at)) : $date
            ];
        }
        return $result;
    }
}
