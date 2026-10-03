<?php
/**
 * @filesource modules/product/views/shop.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Shop;

use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;
use Web\Gcms;

/**
 * Storefront pages of a product module: checkout, my orders and an order's
 * detail — the themes' product/checkout.html, myorders.html and
 * orderdetail.html. The pages fill themselves from the product APIs; this
 * view hands them this module instance's API and page addresses.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * @var array page => title
     */
    private static $titles = [
        'checkout' => 'Make payment',
        'myorders' => 'My Orders',
        'orderdetail' => 'Order detail'
    ];

    /**
     * Render one of \Product\Index\Controller::$pages.
     *
     * @param object $index
     * @param string $page  checkout|myorders|orderdetail
     *
     * @return object
     */
    public function render($index, $page)
    {
        if ($page !== 'checkout' && !\Web\Login::isMember()) {
            // Orders belong to a member account
            header('Location: '.WEB_URL.'login');
            exit;
        }

        $moduleUrl = Gcms::createUrl($index->module);
        if (!Gcms::$menu->isHomeMenu($index->index_id)) {
            Gcms::$view->addBreadcrumb($moduleUrl, $index->topic, $index->description);
        }
        if ($page === 'orderdetail') {
            Gcms::$view->addBreadcrumb(\Product\Index\Controller::pageUrl($index->module, 'myorders'), Language::get('My Orders'));
        }
        $index->topic = Language::get(self::$titles[$page]);
        $index->description = '';
        $index->canonical = \Product\Index\Controller::pageUrl($index->module, $page);
        Gcms::$view->addBreadcrumb($index->canonical, $index->topic);

        $template = Template::create($index->owner, $index->module, $page);
        $template->add(self::values($index) + [
            '/{TOPIC}/' => Text::htmlspecialchars($index->topic)
        ]);
        $index->detail = $template->render();
        self::context($index);

        return $index;
    }

    /**
     * Addresses of this module instance for the storefront templates:
     * {MODULE_ID}, {SHOP_URL}, {CHECKOUT_URL}, {MYORDERS_URL} and the
     * {MYORDERS_Q} / {ORDERDETAIL_Q} forms ending in ? or & for a query.
     *
     * @param object $index
     *
     * @return array
     */
    public static function values($index)
    {
        $myorders = \Product\Index\Controller::pageUrl($index->module, 'myorders');
        $orderdetail = \Product\Index\Controller::pageUrl($index->module, 'orderdetail');
        $query = fn($url) => $url.(strpos($url, '?') === false ? '?' : '&');

        return [
            '/{MODULE_ID}/' => (int) $index->module_id,
            '/{SHOP_URL}/' => Text::htmlspecialchars(Gcms::createUrl($index->module)),
            '/{CHECKOUT_URL}/' => Text::htmlspecialchars(\Product\Index\Controller::pageUrl($index->module, 'checkout')),
            '/{MYORDERS_URL}/' => Text::htmlspecialchars($myorders),
            '/{MYORDERS_Q}/' => Text::htmlspecialchars($query($myorders)),
            '/{ORDERDETAIL_Q}/' => Text::htmlspecialchars($query($orderdetail))
        ];
    }

    /**
     * Put the theme's product/shop.html (which module the page belongs to +
     * the floating cart button) before </body>. Every public page gets the
     * first product module's (\Product\Init\Controller::init()), so the cart
     * stays reachable outside the store; a storefront page calls this again
     * with its own module, replacing it.
     *
     * @param object $index
     */
    public static function context($index)
    {
        $template = Template::create($index->owner, $index->module, 'shop');
        $template->add(self::values($index));
        // the footer is inserted after the page's {LNG_...} pass — translate here
        Gcms::$view->setFooterMetas(['product_shop' => Language::trans($template->render())]);
    }
}
