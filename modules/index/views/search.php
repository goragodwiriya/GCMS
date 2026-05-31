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
use Kotchasan\Language;
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
        $logoHomeHref = Text::htmlspecialchars(WEB_URL.'index.php');
        $formAction = Text::htmlspecialchars(WEB_URL.'index.php');
        $qEsc = Text::htmlspecialchars($q);

        ob_start();
        ?>
<div class="gcms-site-search">
<style>
.gcms-site-search{font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;color:#202124;line-height:1.5;max-width:720px;margin:0 auto;padding:12px 16px 48px;}
.gcms-site-search__bar{display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;}
.gcms-site-search__logo{font-weight:600;font-size:1.05rem;text-decoration:none;color:#202124;}
.gcms-site-search__logo:hover{text-decoration:underline;}
.gcms-site-search__form{flex:1;min-width:200px;display:flex;gap:8px;align-items:center;border:1px solid #dfe1e5;border-radius:24px;padding:8px 14px;box-shadow:0 1px 6px rgba(32,33,36,.1);background:#fff;}
.gcms-site-search__form:focus-within{box-shadow:0 1px 6px rgba(32,33,36,.18);border-color:rgba(223,225,229,0);}
.gcms-site-search__input{flex:1;border:0;outline:0;font-size:16px;background:transparent;min-width:0;}
.gcms-site-search__submit{border:0;background:#1a73e8;color:#fff;border-radius:20px;padding:8px 16px;font-size:14px;cursor:pointer;font-weight:500;}
.gcms-site-search__submit:hover{background:#1557b0;}
.gcms-site-search__stats{font-size:14px;color:#70757a;margin:20px 0 12px;}
.gcms-site-search__result{padding:16px 0;border-bottom:1px solid #ebebeb;}
.gcms-site-search__result:last-child{border-bottom:0;}
.gcms-site-search__title{font-size:20px;line-height:1.3;margin:0 0 4px;font-weight:400;}
.gcms-site-search__title a{color:#1a0dab;text-decoration:none;}
.gcms-site-search__title a:hover{text-decoration:underline;}
.gcms-site-search__url{font-size:14px;color:#006621;line-height:1.3;word-break:break-all;margin-bottom:6px;}
.gcms-site-search__cite{display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:bottom;}
.gcms-site-search__snippet{font-size:14px;color:#4d5156;line-height:1.58;margin:0;}
.gcms-site-search__snippet mark{background:#fff8e1;font-weight:600;padding:0 2px;}
.gcms-site-search__meta{font-size:12px;color:#70757a;margin-top:4px;}
.gcms-site-search__pill{display:inline-block;font-size:11px;background:#f1f3f4;color:#1967d2;border-radius:4px;padding:2px 8px;margin-right:6px;vertical-align:middle;}
.gcms-site-search__empty,.gcms-site-search__hint{color:#70757a;font-size:14px;margin-top:24px;}
.gcms-site-search__pager{display:flex;gap:12px;align-items:center;margin-top:28px;font-size:14px;}
.gcms-site-search__pager a{color:#1a73e8;text-decoration:none;}
.gcms-site-search__pager a:hover{text-decoration:underline;}
.gcms-site-search__error{color:#c5221f;font-size:14px;margin-top:16px;}
</style>

<div class="gcms-site-search__bar">
  <a class="gcms-site-search__logo" href="<?php echo $logoHomeHref; ?>"><?php echo Text::htmlspecialchars(strip_tags(self::$cfg->web_title)); ?></a>
  <form class="gcms-site-search__form" method="get" action="<?php echo $formAction; ?>" role="search">
    <input type="hidden" name="module" value="search">
    <input class="gcms-site-search__input" type="search" name="q" value="<?php echo $qEsc; ?>"
           placeholder="<?php echo Text::htmlspecialchars($searchPlaceholder); ?>"
           autocomplete="off" aria-label="<?php echo Text::htmlspecialchars($btnSearch); ?>">
    <button class="gcms-site-search__submit" type="submit"><?php echo Text::htmlspecialchars($btnSearch); ?></button>
  </form>
</div>

        <?php if ($error !== '') { ?>
<p class="gcms-site-search__error"><?php echo Text::htmlspecialchars($error); ?></p>
        <?php } elseif ($q !== '' && mb_strlen($q) < 2) { ?>
<p class="gcms-site-search__hint"><?php echo Text::htmlspecialchars(Language::get('Try adjusting your search or filter criteria', '')); ?></p>
        <?php } elseif ($q !== '' && $total === 0) { ?>
<p class="gcms-site-search__empty"><?php echo Text::htmlspecialchars(Language::get('Try adjusting your search or filter criteria', '')); ?></p>
        <?php } elseif ($q !== '' && $total > 0) { ?>
<p class="gcms-site-search__stats"><?php echo Text::htmlspecialchars(str_replace('{COUNT}', (string) $total, Language::get('About {COUNT} results', 'About {COUNT} results'))); ?></p>
          <?php foreach ($hits as $hit) {
              echo $this->renderHit($hit, $q);
          }
            echo $this->renderPager($q, $page, $totalPages);
        } else { ?>
<p class="gcms-site-search__hint"><?php echo Text::htmlspecialchars($searchPlaceholder); ?></p>
        <?php } ?>

</div>
        <?php

        return ob_get_clean();
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
        $urlEsc = Text::htmlspecialchars($url);
        $displayUrl = $this->formatDisplayUrl($url);
        $descPlain = trim(strip_tags($hit->description));
        $snippet = $descPlain !== '' ? Gcms::doHighlight(Text::htmlspecialchars($descPlain), $query) : '';
        $titleHtml = Gcms::doHighlight(Text::htmlspecialchars($hit->title), $query);
        $typeKey = $hit->sourceType === 'product' ? 'Product' : 'Document';
        $typeLabel = Language::get($typeKey, $hit->sourceType);

        $scoreNote = '';
        if (defined('DEBUG') && (int) DEBUG > 1) {
            $scoreNote = ' · score '.round($hit->finalScore, 3);
        }

        ob_start();
        ?>
<article class="gcms-site-search__result">
  <h2 class="gcms-site-search__title"><a href="<?php echo $urlEsc; ?>" rel="bookmark"><?php echo $titleHtml; ?></a></h2>
  <div class="gcms-site-search__url"><span class="gcms-site-search__cite"><?php echo Text::htmlspecialchars($displayUrl); ?></span></div>
        <?php if ($snippet !== '') { ?>
  <p class="gcms-site-search__snippet"><?php echo $snippet; ?></p>
        <?php } ?>
  <div class="gcms-site-search__meta"><span class="gcms-site-search__pill"><?php echo Text::htmlspecialchars($typeLabel); ?></span>
        <?php if ($hit->moduleTopic !== '') {
            echo Text::htmlspecialchars($hit->moduleTopic);
        }
        if ($hit->publishedDate !== '') {
            echo ' · '.Text::htmlspecialchars($hit->publishedDate);
        }
        echo Text::htmlspecialchars($scoreNote); ?></div>
</article>
        <?php

        return ob_get_clean();
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

    /**
     * @param string $q
     * @param int    $page
     * @param int    $totalPages
     *
     * @return string
     */
    private function renderPager($q, $page, $totalPages)
    {
        if ($totalPages <= 1) {
            return '';
        }

        $base = WEB_URL.'index.php?module=search&q='.rawurlencode($q).'&page=';
        $prevLabel = Language::get('Previous', 'Previous');
        $nextLabel = Language::get('Next', 'Next');
        $prev = $page > 1 ? '<a href="'.Text::htmlspecialchars($base.($page - 1)).'">← '.Text::htmlspecialchars($prevLabel).'</a>' : '';
        $next = $page < $totalPages ? '<a href="'.Text::htmlspecialchars($base.($page + 1)).'">'.Text::htmlspecialchars($nextLabel).' →</a>' : '';
        $mid = $page.' / '.$totalPages;

        return '<nav class="gcms-site-search__pager" aria-label="Pagination">'.$prev
            .'<span>'.Text::htmlspecialchars($mid).'</span>'.$next.'</nav>';
    }
}
