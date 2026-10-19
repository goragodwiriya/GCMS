<?php
/**
 * @filesource modules/product/controllers/payment.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Product\Payment;

use Kotchasan\Http\Request;

/**
 * Public API: POST /api/product/payment/notify, GET /api/product/payment/methods
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Payment\Controller
{
    /**
     * GET /api/product/payment/methods?module_id=
     *
     * A store is paid into its own account — the module's "bank_info" and
     * "promptpay_id" settings — not the site-wide accounts that
     * api/payment/methods lists (Gcms\Payment\Controller::configuredMethods()),
     * so each product module can take payment into a different account.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function methods(Request $request)
    {
        try {
            \Gcms\Api::validateMethod($request, 'GET');

            $banks = [];
            $promptpay = null;
            $ctx = \Product\Lists\Controller::resolveModule($request);
            $module = $ctx ? \Index\Module\Model::getModuleWithConfig('product', $ctx->module_id) : null;
            if ($module) {
                $config = $module->config;
                $bankInfo = trim((string) ($config->bank_info ?? ''));
                $transfer = !isset($config->transfer_enabled) || $config->transfer_enabled;
                if ($transfer && $bankInfo !== '') {
                    $banks[] = ['key' => 'bank', 'bank_name' => $bankInfo, 'branch' => '', 'account_name' => '', 'account_no' => ''];
                }
                $promptpayId = (string) ($config->promptpay_id ?? '');
                if ($transfer && $promptpayId !== '') {
                    $promptpay = [
                        'key' => 'store',
                        'type' => 'promptpay',
                        'promptpay_id' => $promptpayId,
                        'qr_base' => preg_replace('/\.png$/', '', self::promptPayImageUrl($promptpayId))
                    ];
                }
            }

            return $this->successResponse(['banks' => $banks, 'promptpay' => $promptpay], 'Payment methods retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * @return string
     */
    protected function modelClass()
    {
        return \Product\Payment\Model::class;
    }

    /**
     * `product_order` has no email/url columns — order_no is the only
     * identifying field.
     *
     * @return array
     */
    protected function orderCriteriaFields()
    {
        return ['order_no'];
    }

    /**
     * Record the filename on the product_payment row (via the shared
     * Product\Payment\Model::attachSlip() helper) so admin can see it,
     * instead of the base class's generic disk-only drop — same path the
     * existing admin order-detail view already reads from
     * (Product\Order\Model / Product\Order\Controller::verifyPayment()).
     *
     * @param Request $request
     * @param int     $paymentId product_payment.id (Model::recordPayment()'s
     *                           returned id)
     *
     * @return void
     */
    protected function handleSlip(Request $request, $paymentId)
    {
        foreach ($request->getUploadedFiles() as $field => $file) {
            if (strpos($field, 'slip') === false && strpos($field, 'payment_proof') === false) {
                continue;
            }
            if (!is_object($file) || !method_exists($file, 'hasUploadFile') || !$file->hasUploadFile()) {
                continue;
            }
            // Slip upload failing never rolls back an already-recorded payment.
            \Product\Payment\Model::attachSlip($paymentId, $file, self::$cfg);
        }
    }

    /**
     * product_order has `cust_email`, not `email`.
     *
     * @param array  $criteria
     * @param array  $paymentData
     * @param object $order
     *
     * @return void
     */
    protected function notifyByEmail(array $criteria, array $paymentData, $order)
    {
        $email = isset($order->cust_email) ? $order->cust_email : '';
        if (empty($email)) {
            return;
        }

        \Gcms\EmailTemplate::send('payment_notify', $email, [
            'ORDER_NO' => $order->order_no ?? '',
            'PAID' => number_format($paymentData['paid'], 2),
            'PAYMENT_METHOD' => $paymentData['payment_method'],
            'COMMENT' => $paymentData['comment']
        ]);
    }
}
