<?php
/**
 * @filesource modules/index/views/search.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Search;

use Gcms\Search\SearchAggregator;
use Gcms\Search\SearchContext;
use Gcms\Search\SearchHit;
use Gcms\Search\SearchRegistry;
use Kotchasan\Http\Request;
use Kotchasan\Http\Uri;
use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Google-style merged site search results.
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * @var int
     */
    private $perPage = 10;

    /**
     * @param Request $request
     *
     * @return object
     */
    public function render(Request $request)
    {
        $q = trim($request->get('q')->topic());
        $page = max(1, $request->get('page')->toInt());

        $titleBase = Language::get('Site search', 'Site search');
        $topic = $q !== '' ? $titleBase.' — '.strip_tags($q) : $titleBase;

        $canonical = WEB_URL.'index.php?module=search';
        $qParts = [];
        if ($q !== '') {
            $qParts[] = 'q='.rawurlencode($q);
        }
        if ($page > 1) {
            $qParts[] = 'page='.(int) $page;
        }
        if ($qParts !== []) {
            $canonical .= '?'.implode('&', $qParts);
        }

        Gcms::$view->addBreadcrumb(WEB_URL.'index.php?module=search', $titleBase, Language::get('Search', 'Search'));

        $allHits = [];
        $error = '';

        if ($q !== '' && mb_strlen($q) >= 2) {
            try {
                $aggregator = new SearchAggregator(SearchRegistry::defaults());
                $ctx = new SearchContext();
                $ctx->query = $q;
                $ctx->totalLimit = 80;
                $ctx->perProviderLimit = 40;
                $allHits = $aggregator->search($ctx);
            } catch (\Throwable $e) {
                $error = Language::get('Sorry, cannot find a page called Please check the URL or try the call again.', 'Search failed.');
                $allHits = [];
            }
        }

        $total = count($allHits);
        $offset = ($page - 1) * $this->perPage;
        $pageHits = array_slice($allHits, $offset, $this->perPage);
        $totalPages = max(1, (int) ceil($total / $this->perPage));

        $detail = $this->buildSearchPageHtml($q, $pageHits, $page, $totalPages, $total, $error);

        $description = $total > 0
            ? str_replace('{COUNT}', (string) $total, Language::get('About {COUNT} results', 'About {COUNT} results'))
            : ($q !== '' ? Language::get('Try adjusting your search or filter criteria', '') : '');

        Gcms::$view->setJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $topic,
            'url' => $canonical,
            'description' => $description
        ]);

        return (object) [
            'topic' => $topic,
            'detail' => $detail,
            'description' => $description,
            'keywords' => $q !== '' ? $q : Language::get('Search', 'Search'),
            'module' => 'search',
            'canonical' => $canonical,
            'noindex' => 0
        ];
    }

    /**
     * @param string      $q
     * @param SearchHit[] $hits
     * @param int         $page
     * @param int         $totalPages
     * @param int         $total
     * @param string      $error
     *
     * @return string
     */
    private function buildSearchPageHtml($q, array $hits, $page, $totalPages, $total, $error)
    {
        $searchPlaceholder = Language::get('Type to search', '');
        $btnSearch = Language::get('Search', 'Search');

        $statusClass = 'gcms-site-search__hint';
        $statusText = $searchPlaceholder;
        $resultsHtml = '';

        if ($error !== '') {
            $statusClass = 'gcms-site-search__error';
            $statusText = $error;
        } elseif ($q !== '' && mb_strlen($q) < 2) {
            $statusClass = 'gcms-site-search__hint';
            $statusText = Language::get('Try adjusting your search or filter criteria', '');
        } elseif ($q !== '' && $total === 0) {
            $statusClass = 'gcms-site-search__empty';
            $statusText = Language::get('Try adjusting your search or filter criteria', '');
        } elseif ($q !== '' && $total > 0) {
            $statusClass = 'gcms-site-search__stats';
            $statusText = str_replace('{COUNT}', (string) $total, Language::get('About {COUNT} results', 'About {COUNT} results'));
            foreach ($hits as $hit) {
                $resultsHtml .= $this->renderHit($hit, $q);
            }
        }

        $uri = Uri::createFromUri(WEB_URL.'index.php?module=search');
        if ($q !== '') {
            $uri = $uri->withParams(['q' => $q]);
        }
        $pagination = ($q !== '' && $totalPages > 1) ? $uri->pagination($totalPages, $page) : '';

        $template = Template::create('index', 'search', 'search');
        $template->add([
            '/{SEARCH_HOME_URL}/' => Text::htmlspecialchars(WEB_URL.'index.php'),
            '/{WEBTITLE}/' => Text::htmlspecialchars(strip_tags(self::$cfg->web_title)),
            '/{FORM_ACTION}/' => Text::htmlspecialchars(WEB_URL.'index.php'),
            '/{SEARCH_PLACEHOLDER}/' => Text::htmlspecialchars($searchPlaceholder),
            '/{BTN_SEARCH}/' => Text::htmlspecialchars($btnSearch),
            '/{QUERY}/' => Text::htmlspecialchars($q),
            '/{STATUS_CLASS}/' => Text::htmlspecialchars($statusClass),
            '/{STATUS_TEXT}/' => Text::htmlspecialchars($statusText),
            '/{RESULTS}/' => $resultsHtml,
            '/{PAGINATION}/' => $pagination
        ]);

        return $template->render();
    }

    /**
     * @param SearchHit $hit
     * @param string    $query
     *
     * @return string
     */
    private function renderHit(SearchHit $hit, $query)
    {
        $url = $hit->url;
        $descPlain = trim(strip_tags($hit->description));
        $snippet = $descPlain !== '' ? Gcms::doHighlight(Text::htmlspecialchars($descPlain), $query) : '';
        $snippetBlock = $snippet !== '' ? '<p class="gcms-site-search__snippet">'.$snippet.'</p>' : '';

        $titleHtml = Gcms::doHighlight(Text::htmlspecialchars($hit->title), $query);
        $displayUrl = Text::htmlspecialchars($this->formatDisplayUrl($url));

        $typeKey = $hit->sourceType === 'product' ? 'Product' : 'Document';
        $typeLabel = Language::get($typeKey, $hit->sourceType);

        $meta = '';
        if ($hit->moduleTopic !== '') {
            $meta .= Text::htmlspecialchars($hit->moduleTopic);
        }
        if ($hit->publishedDate !== '') {
            $meta .= ($meta !== '' ? ' · ' : '').Text::htmlspecialchars($hit->publishedDate);
        }
        if (defined('DEBUG') && (int) DEBUG > 1) {
            $meta .= ($meta !== '' ? ' · ' : '').Text::htmlspecialchars('score '.round($hit->finalScore, 3));
        }

        $template = Template::create('index', 'search', 'searchitem');
        $template->add([
            '/{URL}/' => Text::htmlspecialchars($url),
            '/{TITLE}/' => $titleHtml,
            '/{DISPLAY_URL}/' => $displayUrl,
            '/{SNIPPET}/' => $snippetBlock,
            '/{TYPE_LABEL}/' => Text::htmlspecialchars($typeLabel),
            '/{META}/' => $meta
        ]);

        return $template->render();
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function formatDisplayUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $p = @parse_url($url);
        if (!is_array($p)) {
            return $url;
        }

        $host = isset($p['host']) ? $p['host'] : '';
        $path = isset($p['path']) ? $p['path'] : '';
        $q = isset($p['query']) ? '?'.$p['query'] : '';

        if ($host !== '') {
            return $host.$path.$q;
        }

        return ltrim($path.$q, '/');
    }
}
