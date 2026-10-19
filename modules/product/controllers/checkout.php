<?php
/**
 * @filesource modules/product/controllers/checkout.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Checkout;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Storefront checkout API.
 *
 * GET  /api/product/checkout/get  -> customer prefill + province options
 * POST /api/product/checkout/save -> place order from cart
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/product/checkout/get
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            $member_id = ($login && isset($login->id)) ? (int) $login->id : 0;

            $info = \Product\Checkout\Model::getCustomerInfo($member_id);
            $info['options'] = ['province' => []];
            // the order part of the page (cart, shipping, payment, totals)
            $ctx = \Product\Lists\Controller::resolveModule($request);
            if ($ctx) {
                $module = \Index\Module\Model::getModuleWithConfig('product', $ctx->module_id);
                $guestToken = isset($_COOKIE[\Product\Cart\Model::COOKIE]) ? preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE[\Product\Cart\Model::COOKIE]) : '';
                $cart = \Product\Cart\Model::findCart($module->id, $member_id, $member_id > 0 ? '' : $guestToken);
                $info += \Product\Checkout\Model::summary($module, $cart);
            }

            return $this->successResponse($info, 'Checkout info retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/checkout/save
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);

            $ctx = \Product\Lists\Controller::resolveModule($request);
            if (!$ctx) {
                return $this->errorResponse('No product module', 404);
            }
            $module = \Index\Module\Model::getModuleWithConfig('product', $ctx->module_id);

            $login = $this->authenticateRequest($request);
            $member_id = ($login && isset($login->id)) ? (int) $login->id : 0;
            $guestToken = '';
            if (!$member_id) {
                $guestToken = isset($_COOKIE[\Product\Cart\Model::COOKIE]) ? preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE[\Product\Cart\Model::COOKIE]) : '';
            }

            if (!$member_id && $guestToken === '') {
                // No cart cookie means nothing was added to a cart — without
                // this, every cookieless guest would share the '' token cart
                return $this->errorResponse('Cart is empty', 400);
            }
            $cart = \Product\Cart\Model::resolveCart($module->id, $member_id, $guestToken);
            // An empty cart has no shipping choices on the page to complain about
            if (empty(\Product\Cart\Model::getItems($cart->id))) {
                return $this->errorResponse(\Kotchasan\Language::get('Cart is empty'), 400);
            }

            // Validation
            $data = [
                'name' => $request->post('name')->topic(),
                'phone' => $request->post('phone')->topic(),
                'email' => $request->post('email')->email(),
                'address' => $request->post('address')->textarea(),
                // the themes' checkout form names the field provinceID
                'province' => $request->post('province')->topic() ?: $request->post('provinceID')->topic(),
                'zipcode' => $request->post('zipcode')->filter('0-9'),
                'comment' => $request->post('comment')->textarea(),
                // the checkout page's choice: bank / promptpay are both paid by transfer
                'payment_method' => $request->post('payment_choice')->exists()
                    ? ($request->post('payment_choice')->filter('a-z') === 'cod' ? 'cod' : 'bank_transfer')
                    : $request->post('payment_method')->filter('a-z_')
            ];
            $errors = [];
            if ($data['name'] === '') {
                $errors['name'] = 'Please fill in';
            }
            if ($data['phone'] === '') {
                $errors['phone'] = 'Please fill in';
            }
            if ($data['address'] === '') {
                $errors['address'] = 'Please fill in';
            }
            // Only the methods this module offers; 'pos' is the cashier's
            $allowed = [];
            if (!isset($module->config->transfer_enabled) || $module->config->transfer_enabled) {
                $allowed[] = 'bank_transfer';
            }
            if (!isset($module->config->cod_enabled) || $module->config->cod_enabled) {
                $allowed[] = 'cod';
            }
            // A store that ships (has published shipping methods) needs one
            // chosen; a store without them (downloads) has no shipping
            $methods = array_map(fn($o) => (int) $o['value'], \Product\Shipping\Model::options($module->id));
            $data['shipping_method_id'] = $request->post('shipping_method_id')->toInt();
            if (!empty($methods) && !in_array($data['shipping_method_id'], $methods, true)) {
                $errors['shipping_method_id'] = 'Please select';
            } elseif (empty($methods)) {
                $data['shipping_method_id'] = 0;
            }
            if (!in_array($data['payment_method'], $allowed, true)) {
                if (empty($allowed)) {
                    return $this->errorResponse('No payment methods configured', 400);
                }
                $data['payment_method'] = $allowed[0];
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $result = \Product\Checkout\Model::placeOrder($module, $ctx->module, $cart, $member_id, $guestToken, $data, 0);

            // Bank-transfer orders: the storefront checkout form bundles
            // proof-of-payment (ref/slip) into this same request. Record it
            // through the shared Gcms\Payment engine (Product\Payment\Model)
            // — the exact path a customer returning later to
            // api/product/payment/notify would also go through — so the
            // order moves to payment_status=pending_verify for admin review.
            // (COD/POS orders skip this: no proof of payment applies.)
            // Only when the customer actually sent proof (a slip or a
            // transfer reference) — otherwise the unpaid order would show up
            // as "awaiting verification"; they can notify later on the
            // payment page
            $hasSlip = false;
            foreach ($request->getUploadedFiles() as $field => $file) {
                if ((strpos($field, 'slip') !== false || strpos($field, 'payment_proof') !== false)
                    && is_object($file) && method_exists($file, 'hasUploadFile') && $file->hasUploadFile()
                ) {
                    $hasSlip = true;
                }
            }
            if ($data['payment_method'] === 'bank_transfer' && ($hasSlip || $request->post('payment_ref')->topic() !== '')) {
                $paymentResult = \Product\Payment\Model::recordPayment(
                    ['order_no' => $result['order_no']],
                    [
                        'payment_date' => date('Y-m-d H:i:s'),
                        'paid' => $result['grand_total'],
                        'payment_method' => $data['payment_method'],
                        'payment_ref' => $request->post('payment_ref')->topic(),
                        'comment' => $data['comment']
                    ]
                );
                if ($paymentResult['success']) {
                    foreach ($request->getUploadedFiles() as $field => $file) {
                        if (strpos($field, 'slip') === false && strpos($field, 'payment_proof') === false) {
                            continue;
                        }
                        if (is_object($file) && method_exists($file, 'hasUploadFile') && $file->hasUploadFile()) {
                            \Product\Payment\Model::attachSlip($paymentResult['id'], $file, self::$cfg);
                        }
                    }
                }
            }

            // Redirect to the module's own payment hand-off page (guest-safe,
            // no login required — unlike myorders/view) so the customer can
            // see order confirmation / still-pending bank details even
            // without an account.
            $url = \Product\Index\Controller::url($ctx->module, '', 0, true, 'order_no='.$result['order_no'].'&amount='.$result['grand_total']);

            return $this->redirectResponse($url, 'Order placed successfully', 200, 1500);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 409, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
