<?php
/**
 * @filesource modules/payment/controllers/qr.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Payment\Qr;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * PromptPay QR image URL.
 *
 * GET /api/payment/qr?key=KEY&amount=N
 *
 * The account is passed as `key`, not `method`: `method` is a routing key
 * (api/{module}/{method}) and a query string value would take its place.
 *
 * Uses promptpay.io's public image endpoint directly — this platform does
 * not render QR images itself, it only builds the URL from the configured
 * promptpay id and the requested amount.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/payment/qr
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $key = $request->get('key')->topic();
            $amount = $request->get('amount')->toDouble();

            $method = \Gcms\Payment\Controller::configuredMethods()['promptpay'];
            if (!$method || $method['key'] !== $key || empty($method['promptpay_id'])) {
                return $this->errorResponse('PromptPay method not configured', 404);
            }

            $url = \Gcms\Payment\Controller::promptPayImageUrl($method['promptpay_id'], $amount);
            if ($url === '') {
                return $this->errorResponse('PromptPay method not configured', 404);
            }

            return $this->successResponse(['url' => $url], 'QR image URL generated');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
