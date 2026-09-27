<?php
/**
 * @filesource modules/index/controllers/contactsend.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Index\Contactsend;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Text;
use Kotchasan\Validator;

/**
 * Public contact endpoint — POST api/index/contactsend
 *
 * Delivers a visitor's message to every admin-status user (status = 1)
 * across all channels they have configured: e-mail, LINE and Telegram.
 * Shared by the Contact widget (widgets/contact) and the Designer's
 * "Contact form" block (both post here via widgets/contact/script.js).
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * POST api/index/contactsend
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Honeypot — bots fill hidden fields; drop silently as "success".
            if ($request->post('contact_hp')->toString() !== '') {
                return $this->successResponse([], Language::get('Your message was sent successfully'));
            }

            // Light per-session throttle (anti-spam): one submit / 20s.
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $now = time();
            if (!empty($_SESSION['contact_last_sent']) && ($now - (int) $_SESSION['contact_last_sent']) < 20) {
                return $this->errorResponse(Language::get('Please wait a moment before sending again'), 429);
            }

            // Inputs (both the widget and the designer block post these names).
            $name = $request->post('contact_name')->topic(150);
            $email = $request->post('contact_email')->email();
            $phone = $request->post('contact_phone')->topic(50);
            $subject = $request->post('contact_subject')->topic(200);
            if ($subject === '') {
                $subject = $request->post('contact_detail')->topic(200);
            }
            $message = trim($request->post('contact_message')->textarea());

            // Validation
            $errors = [];
            if (mb_strlen($name) < 2) {
                $errors['contact_name'] = Language::get('Please fill in');
            }
            if ($email === '' || !Validator::email($email)) {
                $errors['contact_email'] = Language::get('Please enter a valid email address');
            }
            if ($message === '' && $subject === '') {
                $errors['contact_message'] = Language::get('Please fill in');
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 422);
            }

            // Recipients — every admin (status = 1) with any channel configured.
            $admins = \Kotchasan\Model::createQuery()
                ->select('email', 'line_uid', 'telegram_id')
                ->from('user')
                ->where(['status', 1])
                ->fetchAll();

            $emails = [];
            $lineUids = [];
            $telegramIds = [];
            foreach ($admins as $admin) {
                if (!empty($admin->email) && Validator::email($admin->email)) {
                    $emails[$admin->email] = $admin->email;
                }
                if (!empty($admin->line_uid)) {
                    $lineUids[] = $admin->line_uid;
                }
                if (!empty($admin->telegram_id)) {
                    $telegramIds[] = $admin->telegram_id;
                }
            }

            if (empty($emails) && empty($lineUids) && empty($telegramIds)) {
                return $this->errorResponse(Language::get('No admin recipients are configured'), 500);
            }

            // Compose messages
            $heading = Language::get('New contact message');
            $rows = [
                Language::get('Name').': '.$name,
                Language::get('Email').': '.$email
            ];
            if ($phone !== '') {
                $rows[] = Language::get('Phone').': '.$phone;
            }
            if ($subject !== '') {
                $rows[] = Language::get('Topic').': '.$subject;
            }
            if ($message !== '') {
                $rows[] = Language::get('Message').': '.strip_tags(str_replace('<br>', "\n", $message));
            }
            $plain = $heading."\n".implode("\n", $rows);

            $delivered = 0;
            $mailSubject = $heading.($subject !== '' ? ' — '.$subject : '');

            // ── E-mail ──
            if (!empty($emails)) {
                try {
                    $html = '<h3>'.Text::htmlspecialchars($heading).'</h3>';
                    foreach ($rows as $row) {
                        $html .= '<p>'.nl2br(Text::htmlspecialchars($row)).'</p>';
                    }
                    $result = \Kotchasan\Email::send(implode(',', $emails), $email, $mailSubject, $html);
                    if (!$result->error()) {
                        $delivered++;
                    }
                } catch (\Throwable $e) {
                    // best-effort; other channels still try
                }
            }

            // ── LINE ──
            if (!empty($lineUids)) {
                try {
                    \Gcms\Line::sendTo(array_values(array_unique($lineUids)), $plain);
                    $delivered++;
                } catch (\Throwable $e) {
                    // best-effort
                }
            }

            // ── Telegram ──
            if (!empty($telegramIds)) {
                try {
                    \Gcms\Telegram::sendTo(array_values(array_unique($telegramIds)), $plain);
                    $delivered++;
                } catch (\Throwable $e) {
                    // best-effort
                }
            }

            if ($delivered === 0) {
                return $this->errorResponse(Language::get('Unable to complete the transaction'), 502);
            }

            $_SESSION['contact_last_sent'] = $now;
            \Index\Log\Model::add(0, 'index', 'Index', 'Contact message from '.$name.' <'.$email.'>', 0);

            return $this->successResponse([
                'actions' => [
                    ['type' => 'notification', 'level' => 'success', 'message' => Language::get('Your message was sent successfully')]
                ]
            ], Language::get('Your message was sent successfully'));
        } catch (\Kotchasan\ApiException $e) {
            return $this->errorResponse($e->getMessage(), (int) $e->getCode() ?: 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
