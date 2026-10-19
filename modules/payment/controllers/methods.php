<?php
/**
 * @filesource modules/payment/controllers/methods.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Payment\Methods;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * Configured payment methods (bank accounts, PromptPay).
 *
 * GET /api/payment/methods
 *
 * Called by the shared payment/notify.html partial (Payment\Notify\View),
 * whatever module embeds it. Same data as api/{module}/payment/methods.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET /api/payment/methods
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            return $this->successResponse(\Gcms\Payment\Controller::configuredMethods(), 'Payment methods retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
