<?php
/**
 * @filesource modules/product/views/amp.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Product\Jsonld;

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
        // คืนค่าข้อมูล JSON-LD
        return array(
            '@context' => 'http://schema.org',
            '@type' => 'Product',
            'name' => $index->topic,
            'image' => isset($index->image) ? $index->image : '',
            'description' => $index->description,
            'brand' => array(
                '@type' => 'Brand',
                'name' => strip_tags(self::$cfg->web_title)
            ),
            'offers' => array(
                '@type' => 'Offer',
                'priceCurrency' => $index->currency_unit,
                'price' => isset($index->net[$index->currency_unit]) ? $index->net[$index->currency_unit] : 0,
                'availability' => 'http://schema.org/InStock',
                'seller' => Gcms::$site
            )
        );
    }
}
