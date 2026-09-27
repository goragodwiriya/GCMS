<?php
/**
 * @filesource widgets/document/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Document\Controllers;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget Show latest articles
 *
 * Layouts:
 *  - list            : List of articles with thumbnails, sized by `rows`/`cols`
 *  - (default) icon  : Compact icon-row list of articles, sized by `rows`/`cols`
 *  - card            : Grid of article cards, sized by `rows`/`cols`
 *  - highlight       : Highlight — 1 large + up to 4 small, fixed at 5 items  {WIDGET_DOCUMENT module=news;layout=highlight}
 *  - carousel        : Carousel slide, sized by `limit`  {WIDGET_DOCUMENT module=news;layout=carousel;limit=6}
 *
 * `list`, `icon` and `card` all show `rows` x `cols` items (default 1x3);
 * `limit` is only read by the `carousel` layout.
 *
 * Every layout also accepts `category` — a comma separated list of
 * category_id to restrict the articles to, e.g.
 * {WIDGET_DOCUMENT module=news;layout=thumb;category=1,2,3}
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Display the Widget
     *
     * @param array $query_string  module=, layout=, limit=N
     *
     * @return string
     */
    public function get($query_string)
    {
        if (empty($query_string['module'])) {
            return '';
        }

        $index = Gcms::$module->findByModule($query_string['module']);
        if (!$index) {
            return '';
        }

        $layout = isset($query_string['layout']) ? $query_string['layout'] : 'icon';

        switch ($layout) {
        case 'highlight':
            return $this->renderHighlight($index, $query_string);
        case 'carousel':
            return $this->renderCarousel($index, $query_string);
        default:
            return $this->renderGrid($index, $query_string, $layout);
        }
    }

    /**
     * Parse the `category` param into a list of category_id.
     *
     * The value is authored by hand (or by the Designer's settings form) as
     * "1,2,3"; an empty result means every category.
     *
     * @param array $query_string  module=, layout=, limit=N, category=1,2,3
     *
     * @return array
     */
    private function categoryIds($query_string)
    {
        if (empty($query_string['category'])) {
            return [];
        }

        $ids = array_map('intval', explode(',', $query_string['category']));

        return array_values(array_unique(array_filter($ids, function ($id) {
            return $id > 0;
        })));
    }

    /**
     * Resolve thumbnail URL for an article
     *
     * @param object $article
     * @param object $index   Module index
     *
     * @return string
     */
    private function thumb($article, $index)
    {
        if (!empty($article->picture) && file_exists(ROOT_PATH.DATA_FOLDER.'document/'.$article->picture)) {
            return WEB_URL.DATA_FOLDER.'document/'.$article->picture;
        }
        if (!empty($index->default_icon) && file_exists(ROOT_PATH.$index->default_icon)) {
            return WEB_URL.$index->default_icon;
        }
        return WEB_URL.'images/no-image.webp';
    }

    /**
     * Layout: list / icon / card — all three show `rows` x `cols` items,
     * differing only in the container class that drives their CSS.
     *
     * @param object $index      Module index
     * @param array  $query_string  module=, layout=, rows=N, cols=N
     * @param string $viewClass  Container class: list | icon | thumb
     */
    private function renderGrid($index, $query_string, $viewClass)
    {
        $rows = isset($query_string['rows']) ? (int) $query_string['rows'] : 1;
        $cols = isset($query_string['cols']) ? (int) $query_string['cols'] : 3;
        $rows = max(1, min(20, $rows));
        $cols = max(1, min(4, $cols));

        $articles = \Widgets\Document\Models\Index::getLatest($index->module_id, $rows * $cols, $this->categoryIds($query_string));
        if (empty($articles)) {
            return '';
        }

        // item template
        $listitem = Template::createFromFile('widgets/document/views/item.html');
        foreach ($articles as $article) {
            $listitem->add([
                '/{URL}/' => \Document\Index\Controller::url($index->module, $article->alias, $article->id),
                '/{THUMB}/' => $this->thumb($article, $index),
                '/{TOPIC}/' => Text::htmlspecialchars($article->topic),
                '/{DATE}/' => $article->published_date,
                '/{DATE\s([0-9\-]+(\s[0-9:]+)?)?(\s([^}]+))?}/e' => '\Web\View::formatDate(array(1=>"$1",4=>"$4"))',
                '/{DESCRIPTION}/' => !empty($article->description) ? Text::htmlspecialchars($article->description) : '',
                '/{BADGET}/' => '',
                '/{COLS}/' => 'item block'.\Web\View::columnsToGridSize($cols)
            ]);
        }

        return '<div class="ggrid '.$viewClass.'view">'.$listitem->render().'</div>';
    }

    /**
     * Layout: highlight — 1 large + up to 4 small, fixed at 5 items
     *
     * @param object $index   Module index
     * @param array $query_string  module=, layout=
     *
     * @return string
     */
    private function renderHighlight($index, $query_string)
    {
        $articles = \Widgets\Document\Models\Index::getLatest($index->module_id, 5, $this->categoryIds($query_string));
        if (empty($articles)) {
            return '';
        }

        $feature = array_shift($articles); // Big news
        $smalls = array_slice($articles, 0, 4); // Small news

        // ─── Feature (big) ───────────────────────────────────
        $html = '<div class="widget-doc-highlight thumbview">';
        $featureItem = Template::createFromFile('widgets/document/views/item.html');
        $featureItem->add([
            '/{URL}/' => \Document\Index\Controller::url($index->module, $feature->alias, $feature->id),
            '/{THUMB}/' => $this->thumb($feature, $index),
            '/{TOPIC}/' => Text::htmlspecialchars($feature->topic),
            '/{DATE}/' => $feature->published_date,
            '/{DATE\s([0-9\-]+(\s[0-9:]+)?)?(\s([^}]+))?}/e' => '\Web\View::formatDate(array(1=>"$1",4=>"$4"))',
            '/{DESCRIPTION}/' => !empty($feature->description) ? Text::htmlspecialchars($feature->description) : '',
            '/{BADGET}/' => '<div class="badge highlight">Highlight</div>',
            '/{COLS}/' => 'item'
        ]);
        $html .= $featureItem->render();

        // ─── Small list ───────────────────────────────────────
        if (!empty($smalls)) {
            // item template
            $listitem = Template::createFromFile('widgets/document/views/item.html');
            foreach ($smalls as $article) {
                $listitem->add([
                    '/{URL}/' => \Document\Index\Controller::url($index->module, $article->alias, $article->id),
                    '/{THUMB}/' => $this->thumb($article, $index),
                    '/{TOPIC}/' => Text::htmlspecialchars($article->topic),
                    '/{DATE}/' => $article->published_date,
                    '/{DATE\s([0-9\-]+(\s[0-9:]+)?)?(\s([^}]+))?}/e' => '\Web\View::formatDate(array(1=>"$1",4=>"$4"))',
                    '/{DESCRIPTION}/' => !empty($article->description) ? Text::htmlspecialchars($article->description) : '',
                    '/{BADGET}/' => '',
                    '/{COLS}/' => 'item'
                ]);
            }
            $html .= '<div class="iconview">'.$listitem->render().'</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Layout: carousel — Carousel slide
     *
     * @param object $index   Module index
     * @param array $query_string  module=, layout=, limit=N
     *
     * @return string
     */
    private function renderCarousel($index, $query_string)
    {
        $limit = isset($query_string['limit']) ? (int) $query_string['limit'] : 6;
        $limit = max(1, min(20, $limit));

        $itemsPerMobile = max(1, isset($query_string['mobile']) ? (int) $query_string['mobile'] : 1);
        $itemsPerTablet = max(1, isset($query_string['tablet']) ? (int) $query_string['tablet'] : 2);
        $itemsPerDesktop = max(1, isset($query_string['desktop']) ? (int) $query_string['desktop'] : 3);
        $autoplay = !isset($query_string['autoplay']) || $query_string['autoplay'] !== '0';

        $articles = \Widgets\Document\Models\Index::getLatest($index->module_id, $limit, $this->categoryIds($query_string));
        if (empty($articles)) {
            return '';
        }

        // carousel template
        $listitem = Template::createFromFile('widgets/document/views/carousel.html');
        foreach ($articles as $article) {
            $listitem->add([
                '/{URL}/' => \Document\Index\Controller::url($index->module, $article->alias, $article->id),
                '/{THUMB}/' => $this->thumb($article, $index),
                '/{TOPIC}/' => Text::htmlspecialchars($article->topic),
                '/{DATE}/' => $article->published_date,
                '/{DATE\s([0-9\-]+(\s[0-9:]+)?)?(\s([^}]+))?}/e' => '\Web\View::formatDate(array(1=>"$1",4=>"$4"))',
                '/{DESCRIPTION}/' => !empty($article->description) ? Text::htmlspecialchars($article->description) : ''
            ]);
        }

        // Declared on the container instead of shipped as an inline <script>:
        // the same HTML is served by api/index/widgets/render and injected
        // with innerHTML by the Designer, and innerHTML never executes a
        // <script>, so the carousel stayed a raw stack of links there.
        // js/components/Carousel.js picks [data-carousel] up on load and on
        // Carousel.scan(container) after any later injection.
        $options = json_encode([
            'itemsPerView' => [
                'mobile' => $itemsPerMobile,
                'tablet' => $itemsPerTablet,
                'desktop' => $itemsPerDesktop
            ],
            'autoplay' => $autoplay
        ], JSON_UNESCAPED_SLASHES);

        return '<div class="widget-document-carousel thumbview" data-carousel data-carousel-item=".item" data-carousel-options="'.Text::htmlspecialchars($options).'">'
        .$listitem->render()
            .'</div>';
    }
}
