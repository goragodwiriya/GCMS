<?php
/**
 * @filesource widgets/product/controllers/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Widgets\Product\Controllers;

use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Widget: show store products.
 *
 * Layouts:
 *  - (default) list  : grid of product cards   {WIDGET_PRODUCT module=shop;limit=6}
 *  - carousel        : carousel slide          {WIDGET_PRODUCT module=shop;layout=carousel;limit=8}
 *
 * Both layouts also accept `category` — a comma separated list of category_id
 * to restrict the products to, e.g.
 * {WIDGET_PRODUCT module=shop;layout=carousel;category=1,2,3}
 *
 * The carousel layout additionally accepts `mobile`, `tablet` and `desktop`
 * (items per view) and `autoplay=0`, matching the document widget.
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
     * Render the widget for a {WIDGET_PRODUCT module=...;layout=...;limit=...} tag.
     *
     * @param array $query_string
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

        $limit = isset($query_string['limit']) ? (int) $query_string['limit'] : 6;
        $limit = max(1, min(20, $limit));

        $items = \Product\Lists\Model::getList($index->module_id, $index->module, $this->categoryIds($query_string), '', $limit, 0);
        if (empty($items)) {
            return '';
        }

        $layout = isset($query_string['layout']) ? $query_string['layout'] : 'list';
        $tpl = $layout === 'carousel' ? 'carousel' : 'item';

        $listitem = Template::createFromFile('widgets/product/views/'.$tpl.'.html');
        foreach ($items as $item) {
            $listitem->add([
                '/{ID}/' => $item['id'],
                '/{URL}/' => $item['url'],
                '/{THUMB}/' => $item['thumb'],
                '/{TOPIC}/' => Text::htmlspecialchars($item['topic']),
                '/{PRICE}/' => number_format((float) $item['price'], 2),
                '/{STOCK}/' => (int) $item['stock'],
                '/{BUTTON}/' => $this->cartButton($item)
            ]);
        }

        if ($layout === 'carousel') {
            $itemsPerMobile = max(1, isset($query_string['mobile']) ? (int) $query_string['mobile'] : 1);
            $itemsPerTablet = max(1, isset($query_string['tablet']) ? (int) $query_string['tablet'] : 2);
            $itemsPerDesktop = max(1, isset($query_string['desktop']) ? (int) $query_string['desktop'] : 3);
            $autoplay = !isset($query_string['autoplay']) || $query_string['autoplay'] !== '0';

            // Declared on the container instead of shipped as an inline
            // <script>: the same HTML is served by api/index/widgets/render
            // and injected with innerHTML by the Designer, and innerHTML
            // never executes a <script>. js/components/Carousel.js picks
            // [data-carousel] up on load and on Carousel.scan(container).
            $options = json_encode([
                'itemsPerView' => [
                    'mobile' => $itemsPerMobile,
                    'tablet' => $itemsPerTablet,
                    'desktop' => $itemsPerDesktop
                ],
                'autoplay' => $autoplay
            ], JSON_UNESCAPED_SLASHES);

            return '<div class="widget-product-carousel thumbview" data-carousel data-carousel-item=".item" data-carousel-options="'.Text::htmlspecialchars($options).'">'
                .$listitem->render()
                .'</div>';
        }
        return '<div class="widget-product-latest ggrid">'.$listitem->render().'</div>';
    }

    /**
     * Add-to-cart button matching the Now.js Cart dataset contract.
     *
     * @param array $item
     *
     * @return string
     */
    private function cartButton($item)
    {
        if ((int) $item['stock'] <= 0) {
            return '<span class="badge out-of-stock" data-i18n>Out of stock</span>';
        }
        return '<button class="btn-add-cart icon-cart" data-id="'.$item['id'].'" data-topic="'.Text::htmlspecialchars($item['topic']).'"'
            .' data-price="'.$item['price'].'" data-thumb="'.$item['thumb'].'" data-stock="'.$item['stock'].'"'
            .' onclick="event.preventDefault();event.stopPropagation();if(window.Cart){Cart.addItem(this.dataset);}" aria-label="Add to cart"></button>';
    }
}
