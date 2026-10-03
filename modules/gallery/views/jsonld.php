<?php
/**
 * @filesource modules/gallery/views/jsonld.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gallery\Jsonld;

use Web\Gcms;

/**
 * generate JSON-LD for Bookstore
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\KBase
{
    /**
     * สร้าง JSON-LD สำหรับ WebSite (หน้าแรก)
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

        $name = $page->topic ?? self::$cfg->web_title;
        $description = $page->description ?? self::$cfg->web_description ?? '';
        // choose WebPage for pages with canonical url (detail pages), otherwise WebSite
        $type = (isset($page->canonical) && !empty($page->canonical)) ? 'WebPage' : 'WebSite';

        $result = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            'url' => WEB_URL,
            'description' => $description,
            'image' => $image,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => WEB_URL.'index.php?module=search&q={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];

        // Add breadcrumb if available
        $breadcrumb = Gcms::$view->getBreadcrumbJsonld();
        if (!empty($breadcrumb)) {
            // remove nested @context if breadcrumb returns a full schema
            if (is_array($breadcrumb) && isset($breadcrumb['@context'])) {
                unset($breadcrumb['@context']);
            } elseif (is_object($breadcrumb) && isset($breadcrumb->{'@context'})) {
                unset($breadcrumb->{'@context'});
            }
            $result['breadcrumb'] = $breadcrumb;
        }

        // Add publisher/organization
        if (!empty(Gcms::$site)) {
            $result['publisher'] = Gcms::$site;
        }

        return $result;
    }

    /**
     * สร้าง JSON-LD สำหรับร้านค้า (Organization/Store)
     *
     * @return array
     */
    public static function organization()
    {
        $result = [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => self::$cfg->web_title ?? '',
            'url' => WEB_URL,
            'description' => self::$cfg->web_description ?? ''
        ];

        // Logo
        if (isset(Gcms::$site['logo']['url'])) {
            $result['logo'] = Gcms::$site['logo']['url'];
            $result['image'] = Gcms::$site['logo']['url'];
        }

        // Contact info
        if (!empty(self::$cfg->phone)) {
            $result['telephone'] = self::$cfg->phone;
        }
        if (!empty(self::$cfg->email)) {
            $result['email'] = self::$cfg->email;
        }

        // Address
        if (!empty(self::$cfg->address)) {
            $result['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => self::$cfg->address,
                'addressCountry' => 'TH'
            ];
            if (!empty(self::$cfg->province)) {
                $result['address']['addressRegion'] = self::$cfg->province;
            }
            if (!empty(self::$cfg->zipcode)) {
                $result['address']['postalCode'] = self::$cfg->zipcode;
            }
        }

        // Social profiles
        $sameAs = [];
        $facebookUrl = Gcms::facebookPageUrl(self::$cfg);
        if ($facebookUrl !== '') {
            $sameAs[] = $facebookUrl;
        }
        if (!empty(self::$cfg->line_id)) {
            $sameAs[] = 'https://line.me/ti/p/'.self::$cfg->line_id;
        }
        if (!empty($sameAs)) {
            $result['sameAs'] = $sameAs;
        }

        return $result;
    }

    /**
     * สร้าง JSON-LD สำหรับหนังสือ (Gallery/Book)
     *
     * @param object $item ข้อมูลหนังสือ
     *
     * @return array
     */
    public static function book($item)
    {
        $result = [
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => $item->topic ?? $item->title ?? '',
            'url' => $item->url ?? $item->canonical ?? ''
        ];

        // Description
        if (!empty($item->description)) {
            $result['description'] = $item->description;
        }

        // Image
        if (!empty($item->image)) {
            $result['image'] = $item->image;
        }

        // ISBN
        if (!empty($item->isbn)) {
            $result['isbn'] = $item->isbn;
        }

        // Author
        if (!empty($item->author)) {
            $result['author'] = [
                '@type' => 'Person',
                'name' => $item->author
            ];
        }

        // Publisher
        if (!empty($item->publisher)) {
            $result['publisher'] = [
                '@type' => 'Organization',
                'name' => $item->publisher
            ];
        }

        // Date published
        if (!empty($item->published_date)) {
            $result['datePublished'] = $item->published_date;
        }

        // Number of pages
        if (!empty($item->pages)) {
            $result['numberOfPages'] = (int) $item->pages;
        }

        // Book format
        if (!empty($item->format)) {
            // Paperback, Hardcover, EBook, AudioBook
            $result['bookFormat'] = 'https://schema.org/'.$item->format;
        }

        // Language
        if (!empty($item->language)) {
            $result['inLanguage'] = $item->language;
        } else {
            $result['inLanguage'] = 'th';
        }

        // Category
        if (!empty($item->category)) {
            $result['genre'] = $item->category;
        }

        return $result;
    }

    /**
     * สร้าง JSON-LD สำหรับสินค้า (Gallery) พร้อมราคา
     *
     * @param object $item ข้อมูลสินค้า/หนังสือ
     *
     * @return array
     */
    public static function gallery($item)
    {
        $result = [
            '@context' => 'https://schema.org',
            '@type' => 'Gallery',
            'name' => $item->topic ?? $item->title ?? '',
            'url' => $item->url ?? $item->canonical ?? ''
        ];

        // Description
        if (!empty($item->description)) {
            $result['description'] = $item->description;
        }

        // Image
        if (!empty($item->image)) {
            $result['image'] = is_array($item->image) ? $item->image : [$item->image];
        }

        // SKU / Gallery ID
        if (!empty($item->sku)) {
            $result['sku'] = $item->sku;
        } elseif (!empty($item->id)) {
            $result['sku'] = 'BOOK-'.$item->id;
        }

        // ISBN (for books)
        if (!empty($item->isbn)) {
            $result['gtin13'] = $item->isbn;
        }

        // Brand / Publisher
        if (!empty($item->publisher)) {
            $result['brand'] = [
                '@type' => 'Brand',
                'name' => $item->publisher
            ];
        }

        // Category
        if (!empty($item->category)) {
            $result['category'] = $item->category;
        }

        // Offers (pricing)
        if (isset($item->price)) {
            $offer = [
                '@type' => 'Offer',
                'url' => $item->url ?? $item->canonical ?? '',
                'priceCurrency' => 'THB',
                'price' => (float) $item->price,
                'availability' => 'https://schema.org/InStock'
            ];

            // Stock status
            if (isset($item->stock)) {
                if ($item->stock <= 0) {
                    $offer['availability'] = 'https://schema.org/OutOfStock';
                } elseif ($item->stock < 5) {
                    $offer['availability'] = 'https://schema.org/LimitedAvailability';
                }
            }

            // Condition (usually new for books)
            $offer['itemCondition'] = 'https://schema.org/NewCondition';
            if (!empty($item->condition) && $item->condition === 'used') {
                $offer['itemCondition'] = 'https://schema.org/UsedCondition';
            }

            // Seller
            $offer['seller'] = [
                '@type' => 'Organization',
                'name' => self::$cfg->web_title ?? ''
            ];

            // Valid date
            $offer['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));

            $result['offers'] = $offer;
        }

        // Aggregate rating
        if (!empty($item->rating) && !empty($item->review_count)) {
            $result['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $item->rating,
                'reviewCount' => (int) $item->review_count,
                'bestRating' => 5,
                'worstRating' => 1
            ];
        }

        return $result;
    }

    /**
     * สร้าง JSON-LD สำหรับรายการสินค้า (ItemList)
     *
     * @param object $index ข้อมูลหน้ารายการ
     * @param string $itemType ประเภทของ item (default: Gallery)
     *
     * @return array
     */
    public static function itemList($index, $itemType = 'Gallery')
    {
        $items = [];
        if (!empty($index->items)) {
            foreach ($index->items as $n => $item) {
                $listItem = [
                    '@type' => 'ListItem',
                    'position' => $n + 1,
                    'item' => [
                        '@type' => $itemType,
                        'name' => $item->topic ?? $item->title ?? '',
                        'url' => $item->url ?? ''
                    ]
                ];

                // Add image if available
                if (!empty($item->image)) {
                    $listItem['item']['image'] = $item->image;
                }

                // Add price for gallerys
                if ($itemType === 'Gallery' && isset($item->price)) {
                    $listItem['item']['offers'] = [
                        '@type' => 'Offer',
                        'price' => (float) $item->price,
                        'priceCurrency' => 'THB'
                    ];
                }

                $items[] = $listItem;
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $index->topic ?? '',
            'description' => $index->description ?? '',
            'numberOfItems' => count($items),
            'itemListElement' => $items
        ];
    }

    /**
     * สร้าง JSON-LD สำหรับหน้าค้นหา
     *
     * @param object $index
     *
     * @return array
     */
    public static function search($index)
    {
        $items = [];
        if (!empty($index->items)) {
            foreach ($index->items as $n => $item) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $n + 1,
                    'item' => [
                        '@type' => 'Book',
                        'name' => $item->topic ?? $item->title ?? '',
                        'url' => $item->url ?? ''
                    ]
                ];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'SearchResultsPage',
            'mainEntity' => [
                '@type' => 'ItemList',
                'name' => $index->topic ?? 'Search Results',
                'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
                'numberOfItems' => count($items),
                'itemListElement' => $items
            ]
        ];
    }

    /**
     * สร้าง JSON-LD สำหรับ Breadcrumb
     *
     * @param array $breadcrumbs รายการ breadcrumb [['title' => '', 'url' => ''], ...]
     *
     * @return array
     */
    public static function breadcrumb($breadcrumbs)
    {
        $items = [];
        foreach ($breadcrumbs as $n => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $n + 1,
                'name' => $crumb['title'] ?? $crumb['name'] ?? '',
                'item' => $crumb['url'] ?? ''
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
    }

    /**
     * สร้าง JSON-LD หลายรายการรวมกัน
     *
     * @param array $schemas รายการ schema arrays
     *
     * @return array
     */
    public static function combine(array $schemas)
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_map(function ($schema) {
                unset($schema['@context']);
                return $schema;
            }, $schemas)
        ];
    }
}
