<?php
/**
 * @filesource modules/documentation/views/amp.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Documentation\Jsonld;

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
        return array(
            '@context' => 'http://schema.org',
            '@type' => 'TechArticle',
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => Gcms::createUrl($index->module),
                'publisher' => Gcms::$site
            ),
            'headline' => $index->topic,
            'description' => $index->description,
            'url' => $index->canonical
        );
    }
}
