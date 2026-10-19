<?php
/**
 * @filesource Gcms/Payment/Controller.php
 *
 * Base API controller for the central payment module.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Gcms\Payment;

use Gcms\Api as ApiController;
use Kotchasan\File;
use Kotchasan\Http\Request;

/**
 * Central Payment base Controller.
 *
 * Public (no login required) — this only ever records a payment against an
 * order that already exists elsewhere. A concrete destination extends this
 * class and supplies which \Gcms\Payment\Model subclass to use and which
 * request fields identify the order:
 *
 *   class Controller extends \Gcms\Payment\Controller
 *   {
 *       protected function modelClass() { return \Product\Payment\Model::class; }
 *       protected function orderCriteriaFields() { return ['order_no']; }
 *   }
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
abstract class Controller extends \Gcms\Api
{
    /**
     * The \Gcms\Payment\Model subclass bound to this destination table.
     *
     * @return string
     */
    abstract protected function modelClass();

    /**
     * Request fields that identify the order (subset of
     * order_no/email/url/package). Used only to know which fields to read
     * from the request — the actual matching rules live in
     * Model::findOrder().
     *
     * @return array
     */
    abstract protected function orderCriteriaFields();

    /**
     * GET .../methods
     * Bank list + promptpay info of the site (see configuredMethods()),
     * shaped for the notify-payment form. Generic — no subclass override needed.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function methods(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            return $this->successResponse(self::configuredMethods(), 'Payment methods retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Payment methods configured for the site: bank accounts and the
     * PromptPay account. Also served on its own at GET api/payment/methods
     * (Payment\Methods\Controller), which the shared payment/notify.html
     * partial calls.
     *
     * Read from self::$cfg->payments_method / promptpay_id when the site has
     * them, otherwise from the company settings (company.bank, bank_name,
     * bank_no, promptpay_id) — a single site is paid into its own account.
     * Neither key is declared on Gcms\Config, so both are read with isset().
     *
     * @return array{banks: array, promptpay: array|null}
     */
    public static function configuredMethods()
    {
        $banks = [];
        $promptpay = null;
        $configured = isset(self::$cfg->payments_method) && is_array(self::$cfg->payments_method) ? self::$cfg->payments_method : [];
        foreach ($configured as $key => $item) {
            if (!is_array($item) && !is_object($item)) {
                // A settings page may save each method as
                // ID => one line of text ("Bank ... account name ... no. ...")
                $banks[] = ['key' => $key, 'bank_name' => (string) $item, 'branch' => '', 'account_name' => '', 'account_no' => ''];
                continue;
            }
            $item = (array) $item;
            if (($item['type'] ?? 'bank') === 'promptpay') {
                $promptpay = ['key' => $key] + $item;
            } else {
                $banks[] = ['key' => $key] + $item;
            }
        }

        $company = isset(self::$cfg->company) ? (array) self::$cfg->company : [];
        if (empty($banks) && !empty($company['bank_no'])) {
            $banks[] = [
                'key' => 'company',
                'bank_name' => (string) ($company['bank'] ?? ''),
                'branch' => '',
                'account_name' => (string) ($company['bank_name'] ?? ''),
                'account_no' => (string) $company['bank_no']
            ];
        }
        $promptpayId = !empty(self::$cfg->promptpay_id) ? self::$cfg->promptpay_id : ($company['promptpay_id'] ?? '');
        if ($promptpay === null && !empty($promptpayId)) {
            $promptpay = ['key' => 'promptpay', 'type' => 'promptpay', 'promptpay_id' => (string) $promptpayId];
        }
        if ($promptpay !== null) {
            // the QR image for an amount is qr_base + '/' + amount + '.png'
            $promptpay['qr_base'] = preg_replace('/\.png$/', '', self::promptPayImageUrl($promptpay['promptpay_id'] ?? ''));
        }

        return ['banks' => $banks, 'promptpay' => $promptpay];
    }

    /**
     * PromptPay QR image (promptpay.io) for an account id and amount.
     *
     * @param string $promptpayId phone number or tax id
     * @param float  $amount      0 = let the payer type the amount
     *
     * @return string '' when the id has no digits
     */
    public static function promptPayImageUrl($promptpayId, $amount = 0.0)
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $promptpayId);
        if ($digits === '') {
            return '';
        }

        return 'https://promptpay.io/'.$digits.($amount > 0 ? '/'.number_format($amount, 2, '.', '') : '').'.png';
    }

    /**
     * POST .../notify
     * Customer self-reports a payment against an existing order.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function notify(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $criteria = $this->collectCriteria($request);
            $errors = [];
            if (empty($criteria['order_no']) && (empty($criteria['email']) || empty($criteria['url']))) {
                $errors['order_no'] = 'Please fill in order number, or email and website address';
            }

            $paymentData = $this->collectPaymentData($request);
            if ($paymentData['paid'] <= 0) {
                $errors['paid'] = 'Please fill in';
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            $modelClass = $this->modelClass();
            $result = $modelClass::recordPayment($criteria, $paymentData);
            if (!$result['success']) {
                return $this->errorResponse($result['message'], 400);
            }

            $this->handleSlip($request, $result['id']);
            $this->notifyByEmail($criteria, $paymentData, $result['order']);

            return $this->successResponse(['id' => $result['id']], $result['message']);
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), (int) $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Read the order-identifying fields named by orderCriteriaFields().
     *
     * @param Request $request
     *
     * @return array
     */
    protected function collectCriteria(Request $request)
    {
        $criteria = [];
        foreach ($this->orderCriteriaFields() as $field) {
            switch ($field) {
                case 'email':
                    $criteria['email'] = $request->post('email')->email();
                    break;
                case 'package':
                    $criteria['package'] = $request->post('package')->toInt();
                    break;
                default:
                    $criteria[$field] = $request->post($field)->topic();
            }
        }

        return $criteria;
    }

    /**
     * Read the payment fields common to every destination.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function collectPaymentData(Request $request)
    {
        $rawDate = trim($request->post('payment_date')->toString());
        $rawTime = trim($request->post('payment_time')->toString());
        $paymentDate = date('Y-m-d H:i:s');
        if ($rawDate !== '') {
            $timestamp = strtotime($rawDate.' '.($rawTime !== '' ? $rawTime : '00:00'));
            if ($timestamp !== false) {
                $paymentDate = date('Y-m-d H:i:s', $timestamp);
            }
        }

        return [
            'payment_date' => $paymentDate,
            'paid' => $request->post('paid')->toDouble(),
            'payment_method' => $request->post('payment_method')->topic(),
            'payment_ref' => $request->post('payment_ref')->topic(),
            'comment' => $request->post('comment')->textarea()
        ];
    }

    /**
     * Store an uploaded payment slip against DATA_FOLDER/payment/slip.
     * Never blocks the response — the payment row is already saved by the
     * time this runs.
     *
     * @param Request $request
     * @param int     $orderId
     *
     * @return void
     */
    protected function handleSlip(Request $request, $orderId)
    {
        $dir = ROOT_PATH.DATA_FOLDER.'payment/slip/';
        foreach ($request->getUploadedFiles() as $field => $file) {
            if (strpos($field, 'slip') === false && strpos($field, 'payment_proof') === false) {
                continue;
            }
            if (!is_object($file) || !method_exists($file, 'hasUploadFile') || !$file->hasUploadFile()) {
                continue;
            }
            if (!File::makeDirectory($dir)) {
                return;
            }
            $name = $orderId.'-'.time().self::$cfg->stored_img_type;
            try {
                $file->resizeImage(self::$cfg->img_typies, $dir, $name, self::$cfg->stored_img_size);
            } catch (\Exception $exc) {
                // Slip upload failing never rolls back an already-recorded payment.
            }
        }
    }

    /**
     * Best-effort confirmation email. A missing template never fails the
     * request — \Gcms\EmailTemplate::send() returns an error string rather
     * than throwing. Template code 'payment_notify' (emailtemplate.code is
     * varchar(20)), rows in modules/product/install/database.sql.
     *
     * @param array  $criteria
     * @param array  $paymentData
     * @param object $order
     *
     * @return void
     */
    protected function notifyByEmail(array $criteria, array $paymentData, $order)
    {
        $email = $criteria['email'] ?? (isset($order->email) ? $order->email : '');
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
