<?php
/**
 * @filesource Web/Seo.php
 *
 * SEO Helper Class
 * สร้าง Meta tags, Open Graph, JSON-LD สำหรับ SEO
 * Output เป็น HTML string สำหรับแทนที่ใน template
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Web;

/**
 * SEO Helper Class
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Seo extends \Kotchasan\KBase
{
    /**
     * Singleton instance
     *
     * @var self
     */
    protected static $instance;

    /**
     * Page title
     *
     * @var string
     */
    protected static $title = '';

    /**
     * Site name
     *
     * @var string
     */
    protected static $siteName = '';

    /**
     * Meta description
     *
     * @var string
     */
    protected static $description = '';

    /**
     * Meta keywords
     *
     * @var string
     */
    protected static $keywords = '';

    /**
     * Canonical URL
     *
     * @var string
     */
    protected static $canonical = '';

    /**
     * Robots directive
     *
     * @var string
     */
    protected static $robots = 'index, follow';

    /**
     * Open Graph data
     *
     * @var array
     */
    protected static $og = [];

    /**
     * Twitter Card data
     *
     * @var array
     */
    protected static $twitter = [];

    /**
     * Additional meta tags
     *
     * @var array
     */
    protected static $metas = [];

    /**
     * JSON-LD schemas
     *
     * @var array
     */
    protected static $schemas = [];

    /**
     * Breadcrumbs
     *
     * @var array
     */
    protected static $breadcrumbs = [];

    /**
     * CSS files
     *
     * @var array
     */
    protected static $css = [];

    /**
     * JavaScript files
     *
     * @var array
     */
    protected static $js = [];

    /**
     * Inline scripts
     *
     * @var array
     */
    protected static $scripts = [];

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::init();
        }
        return self::$instance;
    }

    /**
     * Initialize with default values from config
     */
    public static function init()
    {
        self::$siteName = self::$cfg->web_title ?? '';
        self::$description = self::$cfg->web_description ?? '';
        self::$keywords = self::$cfg->web_keywords ?? '';
    }

    /**
     * Reset all SEO data
     *
     * @return self
     */
    public function reset()
    {
        self::$title = '';
        self::$description = self::$cfg->web_description ?? '';
        self::$keywords = self::$cfg->web_keywords ?? '';
        self::$canonical = '';
        self::$robots = 'index, follow';
        self::$og = [];
        self::$twitter = [];
        self::$metas = [];
        self::$schemas = [];
        self::$breadcrumbs = [];
        return $this;
    }

    /**
     * Set site name
     *
     * @param string $name Site name
     *
     * @return self
     */
    public function setSiteName($name)
    {
        self::$siteName = $name;
        return $this;
    }

    /**
     * Get site name
     *
     * @return string
     */
    public function getSiteName()
    {
        return self::$siteName;
    }

    /**
     * Set page title
     *
     * @param string $title Page title
     * @param bool $appendSiteName Append site name (default: true)
     *
     * @return self
     */
    public function setTitle($title, $appendSiteName = true)
    {
        self::$title = $title;
        if ($appendSiteName && !empty(self::$siteName)) {
            self::$title .= ' | '.self::$siteName;
        }
        return $this;
    }

    /**
     * Get page title
     *
     * @return string
     */
    public function getTitle()
    {
        return self::$title ?: self::$siteName;
    }

    /**
     * Set meta description
     *
     * @param string $description Description text (max 160 chars recommended)
     *
     * @return self
     */
    public function setDescription($description)
    {
        self::$description = mb_substr(trim(strip_tags($description)), 0, 300);
        return $this;
    }

    /**
     * Get meta description
     *
     * @return string
     */
    public function getDescription()
    {
        return self::$description;
    }

    /**
     * Set meta keywords
     *
     * @param string|array $keywords Keywords
     *
     * @return self
     */
    public function setKeywords($keywords)
    {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        self::$keywords = $keywords;
        return $this;
    }

    /**
     * Get meta keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        return self::$keywords;
    }

    /**
     * Set canonical URL
     *
     * @param string $url Canonical URL
     *
     * @return self
     */
    public function setCanonical($url)
    {
        self::$canonical = $url;
        return $this;
    }

    /**
     * Get canonical URL
     *
     * @return string
     */
    public function getCanonical()
    {
        return self::$canonical;
    }

    /**
     * Set robots directive
     *
     * @param string $robots Robots directive
     *
     * @return self
     */
    public function setRobots($robots)
    {
        self::$robots = $robots;
        return $this;
    }

    /**
     * Set Open Graph data
     *
     * @param array $og Open Graph data
     *
     * @return self
     */
    public function setOpenGraph($og)
    {
        self::$og = array_merge(self::$og, $og);
        return $this;
    }

    /**
     * Set Twitter Card data
     *
     * @param array $twitter Twitter Card data
     *
     * @return self
     */
    public function setTwitterCard($twitter)
    {
        self::$twitter = array_merge(self::$twitter, $twitter);
        return $this;
    }

    /**
     * Add custom meta tag
     *
     * @param string $name Meta name
     * @param string $content Meta content
     * @param string $type Tag type ('name' or 'property')
     *
     * @return self
     */
    public function addMeta($name, $content, $type = 'name')
    {
        self::$metas[] = [
            'type' => $type,
            'name' => $name,
            'content' => $content
        ];
        return $this;
    }

    /**
     * Add JSON-LD schema
     *
     * @param string $type Schema type
     * @param array $data Schema data
     *
     * @return self
     */
    public function addSchema($type, $data)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type
        ];
        self::$schemas[] = array_merge($schema, $data);
        return $this;
    }

    /**
     * Add Organization schema
     *
     * @param array $data Additional data
     *
     * @return self
     */
    public function addOrganizationSchema($data = [])
    {
        $schema = [
            'name' => self::$cfg->web_title ?? '',
            'url' => WEB_URL,
            'logo' => WEB_URL.(self::$cfg->logo ?? 'datas/images/logo.png')
        ];

        if (!empty(self::$cfg->phone)) {
            $schema['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => self::$cfg->phone,
                'contactType' => 'customer service'
            ];
        }

        return $this->addSchema('Organization', array_merge($schema, $data));
    }

    /**
     * Add Product schema
     *
     * @param array $product Product data
     *
     * @return self
     */
    public function addProductSchema($product)
    {
        $schema = [
            'name' => $product['name'] ?? '',
            'description' => $product['description'] ?? '',
            'image' => $product['image'] ?? '',
            'sku' => $product['sku'] ?? '',
            'offers' => [
                '@type' => 'Offer',
                'price' => $product['price'] ?? 0,
                'priceCurrency' => $product['currency'] ?? 'THB',
                'availability' => ($product['in_stock'] ?? true)
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock'
            ]
        ];

        if (!empty($product['brand'])) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $product['brand']
            ];
        }

        if (!empty($product['isbn'])) {
            $schema['isbn'] = $product['isbn'];
        }

        if (!empty($product['author'])) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $product['author']
            ];
        }

        return $this->addSchema('Product', $schema);
    }

    /**
     * Add breadcrumb item
     *
     * @param string $title Item title
     * @param string|null $url Item URL
     *
     * @return self
     */
    public function addBreadcrumb($title, $url = null)
    {
        self::$breadcrumbs[] = [
            'title' => $title,
            'url' => $url
        ];
        return $this;
    }

    /**
     * Get breadcrumbs array
     *
     * @return array
     */
    public function getBreadcrumbs()
    {
        return self::$breadcrumbs;
    }

    /**
     * Get breadcrumb items formatted for template rendering
     *
     * @return array
     */
    public function getBreadcrumbItems()
    {
        if (empty(self::$breadcrumbs)) {
            return [];
        }

        $items = [];
        $total = count(self::$breadcrumbs);

        foreach (self::$breadcrumbs as $index => $crumb) {
            $isLast = ($index === $total - 1);
            $items[] = [
                'ITEM_TEXT' => $crumb['title'],
                'ITEM_URL' => $crumb['url'] ?? '',
                'ITEM_POSITION' => $index + 1,
                'ITEM_CLASS' => $isLast ? 'active' : '',
                'has_link' => !$isLast && !empty($crumb['url']),
                'no_link' => $isLast || empty($crumb['url']),
                'has_separator' => !$isLast
            ];
        }

        return $items;
    }

    /**
     * Add CSS file
     *
     * @param string $url CSS file URL
     *
     * @return self
     */
    public function addCSS($url)
    {
        if (!in_array($url, self::$css)) {
            self::$css[] = $url;
        }
        return $this;
    }

    /**
     * Add JavaScript file
     *
     * @param string $url JS file URL
     *
     * @return self
     */
    public function addJS($url)
    {
        if (!in_array($url, self::$js)) {
            self::$js[] = $url;
        }
        return $this;
    }

    /**
     * Add inline script
     *
     * @param string $script JavaScript code
     *
     * @return self
     */
    public function addScript($script)
    {
        self::$scripts[] = $script;
        return $this;
    }

    /**
     * Render all meta tags as HTML string
     *
     * @return string HTML meta tags
     */
    public function renderMeta()
    {
        $html = [];

        // Title
        $title = $this->getTitle();
        $html[] = '<title>'.htmlspecialchars($title).'</title>';

        // Description
        if (!empty(self::$description)) {
            $html[] = '<meta name="description" content="'.htmlspecialchars(self::$description).'">';
        }

        // Keywords
        if (!empty(self::$keywords)) {
            $html[] = '<meta name="keywords" content="'.htmlspecialchars(self::$keywords).'">';
        }

        // Robots
        $html[] = '<meta name="robots" content="'.self::$robots.'">';

        // Canonical
        if (!empty(self::$canonical)) {
            $html[] = '<link rel="canonical" href="'.htmlspecialchars(self::$canonical).'">';
        }

        // Open Graph
        $html[] = $this->renderOpenGraph();

        // Twitter Card
        $html[] = $this->renderTwitterCard();

        // Additional meta tags
        foreach (self::$metas as $meta) {
            $html[] = '<meta '.$meta['type'].'="'.htmlspecialchars($meta['name']).'" content="'.htmlspecialchars($meta['content']).'">';
        }

        return implode("\n  ", array_filter($html));
    }

    /**
     * Render Open Graph tags
     *
     * @return string HTML
     */
    protected function renderOpenGraph()
    {
        $html = [];

        $og = array_merge([
            'title' => $this->getTitle(),
            'description' => self::$description,
            'type' => 'website',
            'url' => self::$canonical ?: (isset($_SERVER['REQUEST_URI']) ? WEB_URL.ltrim($_SERVER['REQUEST_URI'], '/') : WEB_URL),
            'site_name' => self::$siteName,
            'locale' => \Kotchasan\Language::name() === 'th' ? 'th_TH' : 'en_US'
        ], self::$og);

        foreach ($og as $property => $content) {
            if (!empty($content)) {
                $html[] = '<meta property="og:'.$property.'" content="'.htmlspecialchars($content).'">';
            }
        }

        return implode("\n  ", $html);
    }

    /**
     * Render Twitter Card tags
     *
     * @return string HTML
     */
    protected function renderTwitterCard()
    {
        $html = [];

        $twitter = array_merge([
            'card' => 'summary_large_image',
            'title' => $this->getTitle(),
            'description' => self::$description
        ], self::$twitter);

        foreach ($twitter as $name => $content) {
            if (!empty($content)) {
                $html[] = '<meta name="twitter:'.$name.'" content="'.htmlspecialchars($content).'">';
            }
        }

        return implode("\n  ", $html);
    }

    /**
     * Render JSON-LD schemas as HTML string
     *
     * @return string JSON-LD script tags
     */
    public function renderSchema()
    {
        if (empty(self::$schemas)) {
            return '';
        }

        $html = [];
        foreach (self::$schemas as $schema) {
            $html[] = '<script type="application/ld+json">'."\n".
            json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).
                "\n</script>";
        }

        return implode("\n", $html);
    }

    /**
     * Render BreadcrumbList schema
     *
     * @return string JSON-LD for breadcrumbs
     */
    public function renderBreadcrumbSchema()
    {
        if (empty(self::$breadcrumbs)) {
            return '';
        }

        $items = [];
        foreach (self::$breadcrumbs as $position => $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $crumb['title']
            ];

            if (!empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }

            $items[] = $item;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];

        return '<script type="application/ld+json">'."\n".
        json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).
            "\n</script>";
    }

    /**
     * Render CSS links
     *
     * @return string HTML link tags
     */
    public function renderCSS()
    {
        $html = [];
        foreach (self::$css as $url) {
            $html[] = '<link rel="stylesheet" href="'.htmlspecialchars($url).'">';
        }
        return implode("\n  ", $html);
    }

    /**
     * Render JavaScript files and inline scripts
     *
     * @return string HTML script tags
     */
    public function renderJS()
    {
        $html = [];

        foreach (self::$js as $url) {
            $html[] = '<script src="'.htmlspecialchars($url).'"></script>';
        }

        if (!empty(self::$scripts)) {
            $html[] = '<script>'."\n".implode("\n", self::$scripts)."\n".'</script>';
        }

        return implode("\n  ", $html);
    }
}
