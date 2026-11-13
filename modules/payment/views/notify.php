<?php
/**
 * @filesource modules/payment/views/notify.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Payment\Notify;

use Kotchasan\Language;
use Kotchasan\Template;
use Kotchasan\Text;

/**
 * Bank accounts + PromptPay QR + notify-payment form (themes/{skin}/payment/
 * notify.html), shown on the payment page of an order — e.g. a store order
 * (themes/{skin}/payment/page.html, see Product\Index\View::renderPayment()):
 *
 *   $index->topic = Language::get('Order payment');
 *   $index->description = Language::get('Notify payment for your order');
 *   \Payment\Notify\View::create()->page($index,
 *       'api/product/payment/notify', // the CONSUMING module's own notify endpoint
 *       ['order_no' => $order->order_no],
 *       (float) $order->grand_total
 *   );
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Web\View
{
    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * The whole payment page: page.html with the notify partial in its
     * content slot. The consumer sets $index->topic / description first.
     *
     * @param object $index
     * @param string $actionUrl       see render()
     * @param array  $order           see render()
     * @param float  $amount          see render()
     * @param string $methodsEndpoint see render()
     * @param string|null $promptpayId see render()
     * @param string $class           modifier class of the content slot
     *                                (a store order: product-payment)
     *
     * @return object $index
     */
    public function page($index, $actionUrl, array $order, $amount = 0.0, $methodsEndpoint = 'api/payment/methods', $promptpayId = null, $class = '')
    {
        if (!empty($index->canonical)) {
            \Web\Gcms::$view->addBreadcrumb($index->canonical, Language::trans($index->topic));
        }
        $template = Template::create('payment', 'payment', 'page');
        $template->add([
            '/{TOPIC}/' => Text::htmlspecialchars(Language::trans($index->topic)),
            '/{DESCRIPTION}/' => Text::htmlspecialchars(Language::trans((string) $index->description)),
            '/{CLASS}/' => Text::htmlspecialchars($class),
            '/{CONTENT}/' => $this->render($actionUrl, $order, $amount, $methodsEndpoint, $promptpayId)
        ]);
        $index->detail = $template->render();

        return $index;
    }

    /**
     * The notify-payment partial.
     *
     * @param string $actionUrl The consuming module's own notify endpoint,
     *                          e.g. 'api/product/payment/notify'.
     * @param array  $order     Identifying fields to prefill/hide in the
     *                          form: order_no, email, url, package (only
     *                          the ones the consumer's orderCriteriaFields()
     *                          needs are required).
     * @param float  $amount    Amount due, used to build the PromptPay QR
     *                          URL up front.
     * @param string $methodsEndpoint API listing the accounts to pay into;
     *                          the site's own (api/payment/methods) unless the
     *                          consumer takes payment elsewhere (a product
     *                          store is paid into the store's account).
     * @param string|null $promptpayId PromptPay id of the payee for the QR;
     *                          null = the site's
     *
     * @return string
     */
    public function render($actionUrl, array $order, $amount = 0.0, $methodsEndpoint = 'api/payment/methods', $promptpayId = null)
    {
        if ($promptpayId === null) {
            $promptpay = \Gcms\Payment\Controller::configuredMethods()['promptpay'];
            $promptpayId = $promptpay['promptpay_id'] ?? '';
        }
        // the endpoints are site-relative (api/...): a page below the site
        // root, or a friendly URL, would resolve them against its own path
        $absolute = fn($url) => preg_match('#^(https?:)?//#', $url) ? $url : WEB_URL.ltrim($url, '/');
        $template = Template::create('payment', 'payment', 'notify');
        $template->add([
            '/{METHODS_ENDPOINT}/' => Text::htmlspecialchars($absolute($methodsEndpoint)),
            '/{QR_URL}/' => $promptpayId !== '' ? Text::htmlspecialchars(\Gcms\Payment\Controller::promptPayImageUrl($promptpayId, $amount)) : '',
            '/{ACTION_URL}/' => Text::htmlspecialchars($absolute($actionUrl)),
            '/{ORDER_NO}/' => Text::htmlspecialchars((string) ($order['order_no'] ?? '')),
            '/{EMAIL}/' => Text::htmlspecialchars((string) ($order['email'] ?? '')),
            '/{URL}/' => Text::htmlspecialchars((string) ($order['url'] ?? '')),
            '/{PACKAGE}/' => Text::htmlspecialchars((string) ($order['package'] ?? '')),
            // value of an <input type="number">: no thousands separator
            '/{AMOUNT}/' => $amount > 0 ? number_format($amount, 2, '.', '') : '',
            '/{DATE}/' => date('Y-m-d'),
            '/{TIME}/' => date('H:i')
        ]);

        return $template->render();
    }
}
