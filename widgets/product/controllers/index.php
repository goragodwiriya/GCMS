<?php
/**
 * @filesource widgets/product/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Product\Controllers;

use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget: show store products — same layouts and parameters as the document
 * widget (widgets/document/controllers/index.php).
 *
 * Layouts:
 *  - list            : Compact list of products (picture, name, price), sized by `rows`/`cols`
 *  - icon            : Icon-row list with the add-to-cart button, sized by `rows`/`cols`
 *  - (default) thumb : Grid of product cards, sized by `rows`/`cols` (`card` is the same layout)
 *  - highlight       : Highlight — 1 large + up to 4 small, fixed at 5 items  {WIDGET_PRODUCT module=product;layout=highlight}
 *  - carousel        : Carousel slide, sized by `limit`  {WIDGET_PRODUCT module=product;layout=carousel;limit=8}
 *
 * `list`, `icon` and `thumb` show `rows` x `cols` items (default 1x3), e.g.
 * {WIDGET_PRODUCT module=product;layout=thumb;rows=2;cols=4}
 * A tag with `limit` but no `rows` (written before rows/cols existed) still
 * shows `limit` items. `limit` is otherwise only read by the `carousel`
 * layout, which also accepts `mobile`, `tablet`, `desktop` (items per view)
 * and `autoplay=0`.
 *
 * Every layout also accepts `category` — a comma separated list of
 * category_id to restrict the products to, e.g.
 * {WIDGET_PRODUCT module=product;layout=carousel;category=1,2,3}
 *
 * Products are ordered like the store listing (featured first, then newest).
 * The add-to-cart button uses the product module's own contract
 * (.add-to-cart + data-id, handled by modules/product/script.js); products
 * with variants and products out of stock link to their page instead.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Index extends \Kotchasan\Controller
{
    /**
     * Hook called by Index\Widget\Controller::load(). Position-based auto
     * injection is not used by this widget (it renders via the {WIDGET_PRODUCT}
     * template tag), so this is intentionally a no-op.
     *
     * @param object $obj
     * @param array  $item
     *
     * @return void
     */
    public static function widget($obj, $item)
    {
        // No automatic position injection; rendered via the {WIDGET_PRODUCT} tag.
    }

    /**
     * Display the Widget
     *
     * @param array $query_string  module=, layout=, rows=N, cols=N, limit=N, category=1,2,3
     *
     * @return string
     */
    public function get($query_string)
    {
        if (empty($query_string['module']) || !class_exists('Product\\Index\\Model')) {
            return '';
        }

        $index = Gcms::$module->findByModule($query_string['module']);
        if (!$index || $index->owner !== 'product') {
            return '';
        }

        $layout = isset($query_string['layout']) ? $query_string['layout'] : 'thumb';

        switch ($layout) {
        case 'highlight':
            return $this->renderHighlight($index, $query_string);
        case 'carousel':
            return $this->renderCarousel($index, $query_string);
        case 'list':
        case 'icon':
            return $this->renderGrid($index, $query_string, $layout);
        default:
            // thumb, card and anything unknown: product cards
            return $this->renderGrid($index, $query_string, 'thumb');
        }
    }

    /**
     * Parse the `category` param into a list of category_id.
     *
     * The value is written in the theme tag as "1,2,3"; an empty result
     * means every category.
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
     * Template values of one product (views/item.html, views/carousel.html)
     *
     * @param object $item   row from \Widgets\Product\Models\Index::getLatest()
     * @param object $index  Module index
     * @param string $cols   {COLS} — the item's classes
     * @param string $badget {BADGET} — badge over the picture
     *
     * @return array
     */
    private function values($item, $index, $cols, $badget = '')
    {
        if ($badget === '' && $item->is_new) {
            $badget = '<span class="badge badge-new">'.Language::get('New').'</span>';
        }
        $price = number_format((float) $item->price_min, 2);
        if ((float) $item->price_max > (float) $item->price_min) {
            $price .= ' - '.number_format((float) $item->price_max, 2);
        }
        $currency = isset($index->currency) && $index->currency !== '' ? $index->currency : 'THB';

        return [
            '/{ID}/' => (int) $item->id,
            '/{URL}/' => \Product\Index\Controller::url($index->module, $item->alias, $item->id),
            '/{THUMB}/' => $item->thumb,
            '/{TOPIC}/' => Text::htmlspecialchars($item->topic),
            '/{DESCRIPTION}/' => !empty($item->description) ? Text::htmlspecialchars($item->description) : '',
            '/{PRICE}/' => $price,
            '/{CURRENCY}/' => Text::htmlspecialchars(Language::get($currency)),
            '/{BADGET}/' => $badget,
            '/{BUTTON}/' => $this->cartButton($item),
            '/{COLS}/' => $cols
        ];
    }

    /**
     * Add-to-cart button — the product module's contract (.add-to-cart +
     * data-id/data-variant, modules/product/script.js). A product with
     * variants is added from its page, where the variant is chosen.
     *
     * @param object $item
     *
     * @return string
     */
    private function cartButton($item)
    {
        if (!$item->in_stock) {
            return '<span class="badge badge-out">'.Language::get('Out of stock').'</span>';
        }
        if ($item->product_type === 'variable') {
            return '';
        }

        return '<button type="button" class="btn-add-cart add-to-cart icon-cart" data-id="'.(int) $item->id.'" data-variant="0"'
            .' title="'.Text::htmlspecialchars(Language::get('Add to Cart')).'" aria-label="'.Text::htmlspecialchars(Language::get('Add to Cart')).'"></button>';
    }

    /**
     * Layout: list / icon / thumb — all three show `rows` x `cols` items,
     * differing only in the container class that drives their CSS.
     *
     * @param object $index      Module index
     * @param array  $query_string  module=, layout=, rows=N, cols=N
     * @param string $viewClass  Container class: list | icon | thumb
     *
     * @return string
     */
    private function renderGrid($index, $query_string, $viewClass)
    {
        $rows = isset($query_string['rows']) ? (int) $query_string['rows'] : 1;
        $cols = isset($query_string['cols']) ? (int) $query_string['cols'] : 3;
        $rows = max(1, min(20, $rows));
        $cols = max(1, min(4, $cols));
        $count = $rows * $cols;
        if (!isset($query_string['rows']) && isset($query_string['limit'])) {
            // {WIDGET_PRODUCT module=..;limit=N} from before rows/cols
            $count = max(1, min(20, (int) $query_string['limit']));
        }

        $items = \Widgets\Product\Models\Index::getLatest($index->module_id, $count, $this->categoryIds($query_string));
        if (empty($items)) {
            return '';
        }

        // item template
        $listitem = Template::createFromFile('widgets/product/views/item.html');
        foreach ($items as $item) {
            $listitem->add($this->values($item, $index, 'item block'.\Web\View::columnsToGridSize($cols)));
        }

        return '<div class="ggrid widget-product '.$viewClass.'view">'.$listitem->render().'</div>';
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
        $items = \Widgets\Product\Models\Index::getLatest($index->module_id, 5, $this->categoryIds($query_string));
        if (empty($items)) {
            return '';
        }

        $feature = array_shift($items); // Big product
        $smalls = array_slice($items, 0, 4); // Small products

        // ─── Feature (big) ───────────────────────────────────
        $html = '<div class="widget-product-highlight thumbview">';
        $featureItem = Template::createFromFile('widgets/product/views/item.html');
        $featureItem->add($this->values($feature, $index, 'item', '<span class="badge highlight">'.Language::get('Highlight').'</span>'));
        $html .= $featureItem->render();

        // ─── Small list ───────────────────────────────────────
        if (!empty($smalls)) {
            // item template
            $listitem = Template::createFromFile('widgets/product/views/item.html');
            foreach ($smalls as $item) {
                $listitem->add($this->values($item, $index, 'item'));
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
     * @param array $query_string  module=, layout=, limit=N, mobile=N, tablet=N, desktop=N, autoplay=0
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

        $items = \Widgets\Product\Models\Index::getLatest($index->module_id, $limit, $this->categoryIds($query_string));
        if (empty($items)) {
            return '';
        }

        // carousel template
        $listitem = Template::createFromFile('widgets/product/views/carousel.html');
        foreach ($items as $item) {
            $listitem->add($this->values($item, $index, 'item'));
        }

        // Declared on the container instead of shipped as an inline <script>:
        // the same HTML is also served by api/index/widgets/render, and HTML
        // injected with innerHTML never executes a <script>.
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

        return '<div class="widget-product-carousel widget-product thumbview" data-carousel data-carousel-item=".item" data-carousel-options="'.Text::htmlspecialchars($options).'">'
        .$listitem->render()
            .'</div>';
    }
}
