<?php
/**
 * @filesource Gcms/EmailTemplate.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

use Kotchasan\Language;

/**
 * Email Template Service
 *
 * Provides centralized email template management with:
 * - Templates from the {prefix}_emailtemplate table (code + language)
 * - Runtime templates registered by code (register())
 * - Variable substitution (%VAR_NAME%)
 * - Consistent base layout (layout())
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class EmailTemplate extends \Kotchasan\KBase
{
    /**
     * ตัวแปรที่ผู้ส่งแต่ละ code ส่งมาให้ (แสดงในหน้าแก้ไขแม่แบบของแอดมิน)
     * ทุกแม่แบบใช้ %WEBTITLE% %WEBURL% %TIME% %BUTTON_URL% %BUTTON_LABEL% %HEADER_TITLE% ได้เสมอ
     *
     * @var array
     */
    const VARIABLES = [
        'registration' => ['%NAME%', '%USERNAME%', '%PASSWORD%', '%ACTIVATE_SECTION%'],
        'activation' => ['%NAME%', '%ACTIVATE_URL%'],
        'account_approved' => ['%NAME%', '%LOGIN_URL%'],
        'password_reset' => ['%EMAIL%', '%RESET_URL%', '%EXPIRY_MINUTES%'],
        'admin_new_member' => ['%ADMIN_URL%'],
        'payment_notify' => ['%ORDER_NO%', '%PAID%', '%PAYMENT_METHOD%', '%COMMENT%']
    ];

    /**
     * ตัวแปรที่ใช้ได้ในแม่แบบ code นี้
     *
     * @param string $code
     *
     * @return array
     */
    public static function variables(string $code): array
    {
        return array_merge(
            self::VARIABLES[$code] ?? [],
            ['%WEBTITLE%', '%WEBURL%', '%TIME%', '%BUTTON_URL%', '%BUTTON_LABEL%', '%HEADER_TITLE%']
        );
    }

    /**
     * แม่แบบที่ลงทะเบียนตอนทำงาน (ไม่ได้อยู่ในตาราง emailtemplate)
     * เช่นอีเมลแจ้งเตือนที่โมดูลเขียนเนื้อหาเอง ผ่าน Index\Email\Model::sendTemplate()
     *
     * @var array code => แถวแม่แบบ (subject, detail, from_email, copy_to)
     */
    private static $registered = [];

    /**
     * Get template by code
     *
     * @param string $code Template code
     * @param string $lang Language code
     *
     * @return array|null Template data or null if not found
     */
    public static function get(string $code, string $lang = 'th'): ?array
    {
        if (isset(self::$registered[$code])) {
            return self::$registered[$code];
        }
        // ไม่ใช้ cacheOn() — แคชคิวรีอยู่ได้ 1 ชั่วโมงและการ update ไม่ล้างแคช
        // ผู้ดูแลแก้แม่แบบแล้วอีเมลฉบับถัดไปจะยังใช้ข้อความเดิม (ส่งอีเมลไม่บ่อย ไม่ต้องแคช)
        // first(true) — first() คืน object เป็นค่าปริยาย ซึ่งชนกับชนิด ?array
        $template = \Kotchasan\Model::createQuery()
            ->select()
            ->from('emailtemplate')
            ->where([['code', $code], ['language', $lang]])
            ->first(true);

        return $template ? $template : null;
    }

    /**
     * หาแม่แบบที่จะใช้ส่งจริงตามลำดับภาษา
     * ภาษาที่ใช้งานอยู่ → th → ภาษาใดก็ได้ที่มี (ผู้ดูแลอาจเหลือไว้ภาษาเดียว)
     *
     * @param string $code Template code
     *
     * @return array|null
     */
    public static function find(string $code): ?array
    {
        if (isset(self::$registered[$code])) {
            return self::$registered[$code];
        }
        $templates = [];
        $query = \Kotchasan\Model::createQuery()
            ->select()
            ->from('emailtemplate')
            ->where(['code', $code])
            ->orderBy('id');
        foreach ($query->fetchAll(true) as $item) {
            if (!isset($templates[$item['language']])) {
                $templates[$item['language']] = $item;
            }
        }
        foreach ([Language::name(), 'th'] as $lang) {
            if (isset($templates[$lang])) {
                return $templates[$lang];
            }
        }

        return empty($templates) ? null : reset($templates);
    }

    /**
     * ลงทะเบียนแม่แบบชั่วคราว (ใช้ได้ภายในคำขอนี้) แล้วส่งด้วย send($code, ...)
     * เหมือนแม่แบบในฐานข้อมูล
     *
     * @param string $code     รหัสแม่แบบ
     * @param array  $template subject (ข้อความล้วน), body (HTML ที่ผู้เรียกเตรียมและ escape แล้ว
     *                         จะถูกห่อด้วย layout()) หรือ detail (HTML ทั้งฉบับ ไม่ห่อ),
     *                         button (bool แสดงปุ่ม %BUTTON_URL%), from_email, copy_to
     */
    public static function register(string $code, array $template): void
    {
        if (isset($template['detail'])) {
            $detail = (string) $template['detail'];
        } else {
            $detail = self::layout((string) ($template['body'] ?? ''), !empty($template['button']));
        }
        self::$registered[$code] = [
            'code' => $code,
            'language' => '',
            'from_email' => (string) ($template['from_email'] ?? ''),
            'copy_to' => (string) ($template['copy_to'] ?? ''),
            'subject' => (string) ($template['subject'] ?? ''),
            'detail' => $detail
        ];
    }

    /**
     * กรอบอีเมลมาตรฐาน (รูปแบบเดียวกับแม่แบบใน install/database.sql)
     * หัวเรื่อง %HEADER_TITLE% · ปุ่ม %BUTTON_URL% / %BUTTON_LABEL% · ท้ายอีเมล %WEBTITLE% %TIME%
     *
     * @param string $body   เนื้อหา HTML
     * @param bool   $button true แสดงปุ่ม
     *
     * @return string
     */
    public static function layout(string $body, bool $button = false): string
    {
        $html = '<div style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.8;color:#333;background:#f5f5f5;padding:20px">';
        $html .= '<div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #ddd">';
        $html .= '<div style="background:#3b5998;color:#fff;padding:12px 20px;font-size:16px;font-weight:bold">%HEADER_TITLE%</div>';
        $html .= '<div style="padding:20px">'.$body;
        if ($button) {
            $html .= '<p style="margin:20px 0"><a href="%BUTTON_URL%" style="display:inline-block;background:#3b5998;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">%BUTTON_LABEL%</a></p>';
        }
        $html .= '</div>';
        $html .= '<div style="padding:12px 20px;color:#999;font-size:12px;border-top:1px solid #eee"><a href="%WEBURL%">%WEBTITLE%</a> %TIME%</div>';
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Render template with variable substitution
     *
     * @param string $template Template string
     * @param array  $variables Variables to substitute
     *
     * @return string Rendered template
     */
    public static function render(string $template, array $variables, bool $escapeHtml = true): string
    {
        // Replace %VAR_NAME% with values. By default the substituted value is
        // HTML-escaped so user-supplied variables cannot inject markup/script
        // into the email body (stored/reflected XSS). Pass $escapeHtml=false
        // only for non-HTML contexts (e.g. the subject line).
        foreach ($variables as $key => $value) {
            $value = is_scalar($value) ? (string) $value : '';
            if ($escapeHtml) {
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            $template = str_replace('%'.$key.'%', $value, $template);
        }

        return $template;
    }

    /**
     * Send email using template
     *
     * @param string $code      Template code
     * @param string $to        Recipient email
     * @param array  $variables Template variables
     * @param array  $options   buttonUrl, buttonLabel, headerTitle — ใช้ในแม่แบบได้เป็น
     *                          %BUTTON_URL% %BUTTON_LABEL% %HEADER_TITLE%
     *
     * @return bool|string True on success, error message on failure
     */
    public static function send(string $code, string $to, array $variables, array $options = [])
    {
        // ภาษาของผู้ใช้ก่อน ไม่มีค่อยใช้ภาษาไทย แล้วค่อยภาษาใดก็ได้ที่มี
        $template = self::find($code);

        if (!$template) {
            return 'Email template not found: '.$code;
        }

        // Add default variables
        $variables['WEBTITLE'] = $variables['WEBTITLE'] ?? strip_tags((string) (self::$cfg->web_title ?? 'Website'));
        $variables['WEBURL'] = WEB_URL;
        $variables['TIME'] = date('Y-m-d H:i');
        // ลิงก์ของปุ่ม (เช่นลิงก์ยืนยันอีเมลตอนสมัครสมาชิก) ผู้เรียกส่งมาทาง $options
        // ถ้าไม่ส่งมา ปุ่มจะพากลับหน้าเว็บ แทนที่จะเหลือ %BUTTON_URL% ในอีเมล
        $variables['BUTTON_URL'] = $options['buttonUrl'] ?? WEB_URL;
        $variables['BUTTON_LABEL'] = $options['buttonLabel'] ?? $variables['WEBTITLE'];
        $variables['HEADER_TITLE'] = $options['headerTitle'] ?? $variables['WEBTITLE'];

        // Render. Subject is plain text — don't HTML-escape it, but strip CR/LF
        // to prevent mail-header injection (extra Bcc/Cc). Body is HTML-escaped.
        $subject = self::render((string) $template['subject'], $variables, false);
        $subject = str_replace(["\r", "\n"], '', $subject);
        // หน้าแก้ไขแม่แบบเก็บที่อยู่เว็บเป็น {WEBURL} (ย้ายโดเมนแล้วลิงก์/รูปยังถูก)
        $detail = str_replace('{WEBURL}', WEB_URL, (string) $template['detail']);
        $html = self::render($detail, $variables, true);

        // ผู้ส่ง (ตอบกลับ) และสำเนา ตามที่ตั้งไว้ในแม่แบบ ไม่ได้ตั้งใช้ noreply_email
        $from = empty($template['from_email']) ? (self::$cfg->noreply_email ?? null) : $template['from_email'];
        $cc = empty($template['copy_to']) ? '' : (string) $template['copy_to'];

        // Send email
        $mail = \Kotchasan\Email::send($to, $from, $subject, $html, $cc);

        if ($mail->error()) {
            return $mail->getErrorMessage();
        }

        return true;
    }
}
