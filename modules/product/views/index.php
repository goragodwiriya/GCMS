<?php
/**
 * @filesource modules/product/views/index.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Index;

use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Product storefront: the listing (themes' product/list.html + listitem.html)
 * and the page after checkout (payment/page.html, product/ordercreated.html).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * Render the product grid.
     *
     * @param object $index
     *
     * @return object
     */
    public function render($index)
    {
        // A single selected category titles the page
        $categories = \Web\Category::create($index->module_id);
        if (count($index->category_id) === 1) {
            $category = $categories->get(\Product\Category\Model::$type, $index->category_id[0]);
            if ($category) {
                $index->topic = $category->topic;
                $index->description = $category->detail;
            }
        }
        if (Gcms::$menu->isHomeMenu($index->index_id)) {
            $index->canonical = WEB_URL.'index.php';
        } else {
            $index->canonical = empty($index->category_id)
                ? Gcms::createUrl($index->module)
                : \Product\Index\Controller::categoryUrl($index->module, $index->category_id);
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic, $index->description);
        }

        // One listitem.html per product
        $listitem = Template::create($index->owner, $index->module, 'listitem');
        $cols = self::columnsToGridSize($index->cols ?? 4, '3');
        foreach ($index->items as $item) {
            $inStock = (bool) $item->in_stock;
            $listitem->add([
                '/{COLS}/' => $cols,
                '/{ID}/' => (int) $item->id,
                '/{URL}/' => \Product\Index\Controller::url($index->module, $item->alias, $item->id),
                '/{THUMB}/' => $item->image_url,
                '/{TOPIC}/' => Text::htmlspecialchars($item->topic),
                '/{NEW_HIDDEN}/' => self::hidden(\Product\Index\Model::isNew($item->created_at)),
                '/{IN_STOCK_HIDDEN}/' => self::hidden($inStock),
                '/{OUT_OF_STOCK_HIDDEN}/' => self::hidden(!$inStock),
                '/{PRICE}/' => $this->formatPriceRange($item->price_min, $item->price_max),
                // a variable product is added from its page, where the variant is chosen
                '/{CART_HIDDEN}/' => self::hidden($inStock && $item->product_type !== 'variable')
            ]);
        }
        $list = $listitem->hasItem()
            ? $listitem->render()
            : Template::create($index->owner, $index->module, 'empty')->render();

        // Pagination keeps the category / search filter of this page
        $uri = \Kotchasan\Http\Uri::createFromUri($index->canonical);
        if (!empty($index->search)) {
            $uri = $uri->withParams(['search' => $index->search]);
        }

        // The filter sidebar calls api/product/filter, which cannot tell from
        // a pretty URL ({module}/{category}.html) which product module and
        // categories this page shows — the endpoint carries them
        $template = Template::create($index->owner, $index->module, 'list');
        $template->add(\Product\Shop\View::values($index) + [
            '/{LIST}/' => $list,
            '/{PAGINATION}/' => $uri->pagination($index->total_pages, $index->page),
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic),
            '/{DESCRIPTION}/' => Text::htmlspecialchars((string) $index->description),
            '/{CATEGORY_IDS}/' => implode(',', array_map('intval', $index->category_id))
        ]);
        $index->detail = $template->render();
        \Product\Shop\View::context($index);

        return $index;
    }

    /**
     * The page after checkout: nothing to pay for an order paid on delivery
     * (ordercreated.html), otherwise the store's accounts + notify-payment
     * form (the shared payment page).
     *
     * @param object $index
     * @param array  $order  order_no
     * @param float  $amount
     *
     * @return object
     */
    public function renderPayment($index, array $order, $amount = 0.0)
    {
        $placed = \Kotchasan\DB::create()->first('product_order', [['module_id', (int) $index->module_id], ['order_no', $order['order_no']]], ['order_no', 'payment_method', 'grand_total']);
        $index->canonical = Gcms::createUrl($index->module);
        if ($placed && $placed->payment_method === 'cod') {
            $index->topic = Language::get('Order created successfully');
            $index->description = '';
            Gcms::$view->addBreadcrumb($index->canonical, $index->topic);
            $template = Template::create($index->owner, $index->module, 'ordercreated');
            $template->add(\Product\Shop\View::values($index) + [
                '/{ORDER_NO}/' => Text::htmlspecialchars($placed->order_no),
                '/{AMOUNT}/' => number_format((float) $placed->grand_total, 2),
                '/{MYORDERS_HIDDEN}/' => self::hidden((bool) \Web\Login::isMember())
            ]);
            $index->detail = $template->render();

            return $index;
        }
        $index->topic = Language::get('Order payment');
        $index->description = Language::get('Notify payment for your order');
        // product-payment: gcms.css hides the partial's email / website
        // fields, which identify domain and signup orders, not store orders
        return \Payment\Notify\View::create()->page(
            $index,
            'api/product/payment/notify',
            $order,
            $amount,
            'api/product/payment/methods?module_id='.(int) $index->module_id,
            (string) ($index->config->promptpay_id ?? ''),
            'product-payment'
        );
    }

    /**
     * Format a price or price range for display.
     *
     * @param float $min
     * @param float $max
     *
     * @return string
     */
    protected function formatPriceRange($min, $max)
    {
        if ($min == $max) {
            return number_format((float) $min, 2);
        }
        return number_format((float) $min, 2).' - '.number_format((float) $max, 2);
    }

    /**
     * The HTML hidden attribute for a template part that is not shown.
     *
     * @param bool $visible
     *
     * @return string
     */
    public static function hidden($visible)
    {
        return $visible ? '' : 'hidden';
    }
}
