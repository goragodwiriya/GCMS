<?php
/**
 * @filesource modules/product/controllers/cart.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Cart;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Storefront cart API (called by modules/product/script.js).
 *
 * POST /api/product/cart/add     { product_id, variant_id, qty }
 * POST /api/product/cart/update  { key, qty }
 * POST /api/product/cart/remove  { key }
 *
 * Responses: { success, count, subtotal, ... }. These are public (guest) cart
 * mutations, so no CSRF token is required.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Resolve the current identity (logged-in member or guest token cookie).
     *
     * @param Request $request
     *
     * @return array [member_id, guestToken]
     */
    protected function identity(Request $request)
    {
        $login = $this->authenticateRequest($request);
        $member_id = ($login && isset($login->id) && (int) $login->status === 0) ? (int) $login->id : 0;
        // Members may also be admins/staff buying; treat any logged-in user id as member
        if (!$member_id && $login && isset($login->id)) {
            $member_id = (int) $login->id;
        }
        $guestToken = '';
        $cookie = isset($_COOKIE[Model::COOKIE]) ? preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE[Model::COOKIE]) : '';
        if ($member_id && $cookie !== '') {
            // Signed in after shopping as a guest: move the guest carts
            // (one per product module) into the member's carts
            foreach (\Kotchasan\DB::create()->select('product_cart', [['guest_token', $cookie], ['member_id', 0]], [], ['module_id']) as $guest) {
                Model::mergeGuestIntoMember((int) $guest->module_id, $member_id, $cookie);
            }
            setcookie(Model::COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
        }
        if (!$member_id) {
            $guestToken = $cookie;
            if ($guestToken === '') {
                $guestToken = bin2hex(random_bytes(16));
                setcookie(Model::COOKIE, $guestToken, [
                    'expires' => time() + 60 * 60 * 24 * 30,
                    'path' => '/',
                    'samesite' => 'Lax'
                ]);
            }
        }
        return [$member_id, $guestToken];
    }

    /**
     * Find the cart owning a given item and verify it belongs to the identity.
     *
     * @param int $itemId
     * @param int $member_id
     * @param string $guestToken
     *
     * @return object|null cart row
     */
    protected function ownedCart($itemId, $member_id, $guestToken)
    {
        $db = \Kotchasan\DB::create();
        $item = $db->first('product_cart_item', ['id', $itemId], ['cart_id']);
        if (!$item) {
            return null;
        }
        $cart = $db->first('product_cart', ['id', $item->cart_id]);
        if (!$cart) {
            return null;
        }
        if ($member_id > 0 && (int) $cart->member_id === $member_id) {
            return $cart;
        }
        if ($member_id === 0 && $guestToken !== '' && $cart->guest_token === $guestToken) {
            return $cart;
        }
        return null;
    }

    /**
     * POST /api/product/cart/add
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function add(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);

            $product_id = $request->post('product_id')->toInt();
            $variant_id = $request->post('variant_id')->toInt();
            $qty = max(1, $request->post('qty')->toInt());

            $resolved = Model::resolveProduct($product_id, $variant_id);
            if (!$resolved) {
                return $this->errorResponse(\Kotchasan\Language::get('Product not available'), 404);
            }

            list($member_id, $guestToken) = $this->identity($request);
            $cart = Model::resolveCart($resolved->module_id, $member_id, $guestToken);

            // Stock check (products that do not track stock are always available)
            if ($resolved->variant_id > 0 && \Product\Stock\Model::tracksStock($product_id)) {
                $available = \Product\Stock\Model::available($resolved->variant_id);
                if ($available < $qty) {
                    return $this->errorResponse(\Kotchasan\Language::get('Insufficient stock'), 409);
                }
            }

            Model::addItem($cart, $product_id, $resolved->variant_id, $qty, $resolved->price);
            $summary = Model::summary($cart->id);

            return $this->successResponse([
                'success' => true,
                'count' => $summary['count'],
                'subtotal' => $summary['subtotal'],
                'message' => \Kotchasan\Language::get('Added to cart')
            ], \Kotchasan\Language::get('Added to cart'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/cart/update
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function update(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);

            $key = $request->post('key')->toInt();
            $qty = $request->post('qty')->toInt();
            list($member_id, $guestToken) = $this->identity($request);
            $cart = $this->ownedCart($key, $member_id, $guestToken);
            if (!$cart) {
                return $this->errorResponse(\Kotchasan\Language::get('Cart item not found'), 404);
            }

            $line = \Kotchasan\DB::create()->first('product_cart_item', [['id', $key], ['cart_id', $cart->id]], ['product_id', 'variant_id']);
            if ($line && $line->variant_id > 0 && $qty > 0
                && \Product\Stock\Model::tracksStock($line->product_id)
                && \Product\Stock\Model::available($line->variant_id) < $qty
            ) {
                return $this->errorResponse(\Kotchasan\Language::get('Insufficient stock'), 409);
            }

            if ($qty > 0) {
                Model::updateItem($cart, $key, $qty);
            } else {
                Model::removeItem($cart, $key);
            }

            // the checkout page re-renders its order part with this (#checkoutOrder)
            return $this->successResponse($this->checkoutSummary($request, $cart), 'Cart updated');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/product/cart/remove
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function remove(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->initLanguage($request);

            $key = $request->post('key')->toInt();
            list($member_id, $guestToken) = $this->identity($request);
            $cart = $this->ownedCart($key, $member_id, $guestToken);
            if (!$cart) {
                return $this->errorResponse(\Kotchasan\Language::get('Cart item not found'), 404);
            }

            Model::removeItem($cart, $key);

            return $this->successResponse($this->checkoutSummary($request, $cart), 'Item removed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/product/cart — current cart contents (for cart page).
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $ctx = \Product\Lists\Controller::resolveModule($request);
            if (!$ctx) {
                return $this->successResponse(['items' => [], 'count' => 0, 'subtotal' => 0], 'No product module');
            }
            list($member_id, $guestToken) = $this->identity($request);
            // Reading the cart (every storefront page does, for the cart
            // button) must not create one for each visitor
            $cart = Model::findCart($ctx->module_id, $member_id, $guestToken);
            if (!$cart) {
                return $this->successResponse(['items' => [], 'count' => 0, 'subtotal' => 0], 'Cart retrieved');
            }
            $summary = Model::summary($cart->id);
            $items = Model::getItems($cart->id);

            return $this->successResponse([
                'items' => $items,
                'count' => $summary['count'],
                'subtotal' => $summary['subtotal'],
                // fee of each shipping method for this cart (checkout)
                'shipping_methods' => \Product\Shipping\Model::quotes($ctx->module_id, (float) $summary['subtotal'], Model::weightOf($items))
            ], 'Cart retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET /api/product/cart/checkout — the order part of the checkout page for
     * the chosen shipping method / payment (the shipping radios ask for it)
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function checkout(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);
            $ctx = \Product\Lists\Controller::resolveModule($request);
            if (!$ctx) {
                return $this->errorResponse(\Kotchasan\Language::get('No product module'), 404);
            }
            list($member_id, $guestToken) = $this->identity($request);

            return $this->successResponse($this->checkoutSummary($request, Model::findCart($ctx->module_id, $member_id, $guestToken)), 'Cart retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Product\Checkout\Model::summary() with the shipping method and payment
     * the customer has chosen on the checkout page
     *
     * @param Request     $request
     * @param object|null $cart
     *
     * @return array
     */
    private function checkoutSummary(Request $request, $cart)
    {
        $module_id = $cart ? (int) $cart->module_id : (int) (\Product\Lists\Controller::resolveModule($request)->module_id ?? 0);
        $module = \Index\Module\Model::getModuleWithConfig('product', $module_id);

        return \Product\Checkout\Model::summary(
            $module,
            $cart,
            $request->request('shipping_method_id')->toInt(),
            $request->request('payment_choice')->filter('a-z')
        );
    }
}
