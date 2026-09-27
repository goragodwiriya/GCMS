<?php
/**
 * @filesource modules/document/views/jsonld.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Document\Jsonld;

use Web\Gcms;

/**
 * Generate JSON-LD for Document (News / Article) module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Kotchasan\KBase
{
    /**
     * Entry point: สร้าง JSON-LD สำหรับหน้าบทความเดี่ยว
     * เรียกจาก Document\View\View::render()
     *
     * @param object $index  Module index พร้อม $index->article และ $index->canonical
     *
     * @return array  schema array ส่งให้ setJsonLd()
     */
    public static function generate($index)
    {
        $article = $index->article ?? null;
        if (!$article) {
            return self::webpage($index);
        }

        $schemas = [];

        // ── NewsArticle ───────────────────────────────────────
        $schemas[] = self::article($index);

        // ── BreadcrumbList (จาก BaseView) ────────────────────
        $breadcrumb = \Web\Gcms::$view->getBreadcrumbJsonld();
        if (!empty($breadcrumb)) {
            unset($breadcrumb['@context']);
            $schemas[] = $breadcrumb;
        }

        // ── Organization (publisher) ──────────────────────────
        $org = self::publisherOrganization();
        if ($org) {
            unset($org['@context']);
            $schemas[] = $org;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $schemas
        ];
    }

    /**
     * สร้าง NewsArticle schema จากข้อมูลบทความ
     *
     * @param object $index  Module index พร้อม $index->article และ $index->canonical
     *
     * @return array
     */
    public static function article($index)
    {
        $article = $index->article;
        $canonical = $index->canonical ?? WEB_URL;

        // Resolve image URL (เหมือนที่ view.php คำนวณ)
        $imageUrl = '';
        if (!empty($article->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$article->picture)) {
            $imageUrl = WEB_URL.DATA_FOLDER.'document/'.$article->picture;
        } elseif (!empty($index->default_icon) && file_exists(ROOT_PATH.$index->default_icon)) {
            $imageUrl = WEB_URL.$index->default_icon;
        }

        // Date ISO 8601
        $datePublished = '';
        $dateModified = '';
        if (!empty($article->published_date)) {
            $ts = is_numeric($article->published_date)
                ? (int) $article->published_date
                : strtotime($article->published_date);
            if ($ts) {
                $datePublished = date('c', $ts);
                $dateModified = $datePublished;
            }
        }
        if (!empty($article->updated_date)) {
            $ts = is_numeric($article->updated_date)
                ? (int) $article->updated_date
                : strtotime($article->updated_date);
            if ($ts) {
                $dateModified = date('c', $ts);
            }
        }

        $topic = \Kotchasan\Text::htmlspecialchars($article->topic ?? '');
        $description = mb_substr(trim(strip_tags($article->description ?? '')), 0, 300);

        $schema = [
            '@type' => 'NewsArticle',
            '@id' => $canonical.'#article',
            'headline' => $topic,
            'url' => $canonical,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonical
            ]
        ];

        // Description
        if ($description !== '') {
            $schema['description'] = $description;
        }

        // Image (ImageObject)
        if ($imageUrl !== '') {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $imageUrl
            ];
        }

        // Dates
        if ($datePublished !== '') {
            $schema['datePublished'] = $datePublished;
        }
        if ($dateModified !== '') {
            $schema['dateModified'] = $dateModified;
        }

        // Author: ใช้ชื่อผู้เขียน ถ้าไม่มีให้ใช้ชื่อองค์กร
        $authorName = $article->author_name ?? $article->sender ?? '';
        $schema['author'] = [
            '@type' => empty($authorName) ? 'Organization' : 'Person',
            'name' => $authorName ?: (self::$cfg->web_title ?? '')
        ];

        // Publisher
        $publisher = [
            '@type' => 'Organization',
            'name' => self::$cfg->web_title ?? ''
        ];
        if (isset(Gcms::$site['logo']['url'])) {
            $publisher['logo'] = [
                '@type' => 'ImageObject',
                'url' => Gcms::$site['logo']['url']
            ];
        }
        $schema['publisher'] = $publisher;

        // Keywords / category
        if (!empty($article->keywords)) {
            $schema['keywords'] = $article->keywords;
        } elseif (!empty($article->category_name)) {
            $schema['keywords'] = $article->category_name;
        }

        // Article section (category)
        if (!empty($article->category_name)) {
            $schema['articleSection'] = $article->category_name;
        }

        // Article body (stripped plain text, truncated for schema)
        if (!empty($article->detail)) {
            $body = trim(strip_tags($article->detail));
            if ($body !== '') {
                $schema['articleBody'] = mb_substr($body, 0, 500);
            }
        }

        // Word count estimate
        if (!empty($article->detail)) {
            $wordCount = str_word_count(strip_tags($article->detail));
            if ($wordCount > 0) {
                $schema['wordCount'] = $wordCount;
            }
        }

        // inLanguage
        $schema['inLanguage'] = (defined('LANGUAGE') && LANGUAGE === 'en') ? 'en' : 'th';

        return $schema;
    }

    /**
     * สร้าง Organization schema สำหรับ publisher
     *
     * @return array|null
     */
    protected static function publisherOrganization()
    {
        $name = self::$cfg->web_title ?? '';
        if (empty($name)) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => WEB_URL.'#organization',
            'name' => $name,
            'url' => WEB_URL
        ];

        if (isset(Gcms::$site['logo']['url'])) {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url' => Gcms::$site['logo']['url']
            ];
        }

        return $schema;
    }

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
}
