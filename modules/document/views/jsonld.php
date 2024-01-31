<?php
/**
 * @filesource modules/document/views/amp.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Jsonld;

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
     * สร้างโค้ดสำหรับ JSON-LD
     *
     * @param object $index
     *
     * @return array
     */
    public static function generate($index)
    {
        $suggestedAnswer = array();
        if (!empty($index->comment_items)) {
            foreach ($index->comment_items as $i => $item) {
                $suggestedAnswer[] = array(
                    '@type' => 'Comment',
                    'text' => strip_tags(str_replace(array('{', '}'), array('&#x007B;', '&#x007D;'), $item->detail)),
                    'dateCreated' => date(DATE_ISO8601, $item->last_update),
                    'creator' => array(
                        '@type' => 'Person',
                        'name' => $item->displayname
                    ),
                    'upvoteCount' => 0,
                    'url' => $index->canonical.'#R_'.$item->id
                );
            }
        }
        // คืนค่าข้อมูล JSON-LD
        return array(
            '@context' => 'http://schema.org',
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => Gcms::createUrl($index->module),
                'breadcrumb' => Gcms::$view->getBreadcrumbJsonld()
            ),
            'headline' => $index->topic,
            'datePublished' => date(DATE_ISO8601, $index->create_date),
            'dateModified' => date(DATE_ISO8601, $index->last_update),
            'author' => array(
                '@type' => 'Person',
                'name' => $index->displayname
            ),
            'image' => isset($index->image) ? $index->image : (isset(Gcms::$site['logo']) ? Gcms::$site['logo'] : ''),
            'description' => $index->description,
            'url' => $index->canonical,
            'suggestedAnswer' => $suggestedAnswer
        );
    }
}
