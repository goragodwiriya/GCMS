<?php
/**
 * @filesource Web/Baseview.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Web;

/**
 * View base class for GCMS
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Baseview extends \Kotchasan\View
{
    /**
     * List of breadcrumb items for JSON-LD
     *
     * @var array
     */
    protected $breadcrumbs_jsonld = [];
    /**
     * List of JSON-LD items
     *
     * @var array
     */
    protected $jsonld = [];

    /**
     * Generate JSON-LD data for breadcrumb (BreadcrumbList)
     *
     * @return array
     */
    public function getBreadcrumbJsonld()
    {
        // BreadcrumbList
        if (count($this->breadcrumbs_jsonld) > 1) {
            $elements = [];
            foreach ($this->breadcrumbs_jsonld as $i => $items) {
                $elements[] = [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'item' => $items
                ];
            }
            return [
                '@context' => 'http://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $elements
            ];
        }
        return [];
    }

    /**
     * Configure JSON-LD
     *
     * @param array $datas
     */
    public function setJsonLd($datas)
    {
        $this->jsonld[] = $datas;
    }
}
