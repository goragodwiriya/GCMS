<?php
/**
 * @filesource modules/index/views/jsonld.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Jsonld;

use Gcms\Gcms;

/**
 * generate JSON-LD
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\KBase
{
    /**
     * สร้างโค้ดสำหรับ JSON-LD สำหรับ WebSite
     *
     * @param object $page
     *
     * @return array
     */
    public static function webpage($page)
    {
        $image = [];
        if (isset($page->image)) {
            $image[] = $page->image;
        }
        if (isset(Gcms::$site['logo']['url'])) {
            $image[] = Gcms::$site['logo']['url'];
        }
        $result = [
            '@context' => 'http://schema.org',
            '@type' => 'NewsArticle',
            'url' => $page->canonical,
            'headline' => $page->topic,
            'description' => $page->description,
            'image' => $image,
            'breadcrumb' => Gcms::$view->getBreadcrumbJsonld(),
            'publisher' => Gcms::$site
        ];
        if (isset(self::$cfg->name)) {
            $result['author'] = [
                '@type' => 'Person',
                'name' => self::$cfg->name,
                'url' => WEB_URL.'index.php'
            ];
        }
        return $result;
    }

    /**
     * สร้างโค้ดสำหรับ JSON-LD
     *
     * @param object $index
     *
     * @return array
     */
    public static function search($index)
    {
        // หน้าค้นหา
        $items = [];
        foreach ($index->items as $n => $item) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $n + 1,
                'item' => [
                    '@type' => 'TechArticle',
                    'headline' => $item->topic,
                    'url' => $item->url
                ]
            ];
        }
        return [
            '@context' => 'http://schema.org',
            '@type' => 'SearchResultsPage',
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => $index->topic,
                'itemListOrder' => 'http://schema.org/ItemListOrderAscending',
                'itemListElement' => $items
            ]
        ];
    }
}
