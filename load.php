<?php
/**
 * load.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
/**
 * document root (full path)
 * eg /home/user/public_html/
 *
 * @var string
 */
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)).'/');
/**
 * โฟลเดอร์เก็บข้อมูล
 *
 * @var string
 */
define('DATA_FOLDER', 'datas/');
/**
 * 0 (default) บันทึกเฉพาะข้อผิดพลาดร้ายแรงลง error_log.php
 * 1 บันทึกข้อผิดพลาดและคำเตือนลง error_log.php
 * 2 แสดงผลข้อผิดพลาดและคำเตือนออกทางหน้าจอ (ใช้เฉพาะตอนออกแบบเท่านั้น)
 *
 * @var int
 */
define('DEBUG', 2);
/**
 * กำหนดที่เก็บ error log
 * LOG_FILE   = บันทึกลงไฟล์ error_log.php ของระบบ
 * LOG_SYSTEM = บันทึกไป Apache/PHP error log (error_log())
 * LOG_BOTH   = บันทึกทั้งสองที่
 *
 * @var string
 */
define('LOG_DESTINATION', 'LOG_SYSTEM');
/**
 * false (default)
 * true บันทึกการ query ฐานข้อมูลลง log (ใช้เฉพาะตอนออกแบบเท่านั้น)
 *
 * @var bool
 */
define('DB_LOG', false);
/**
 * ไฟล์เก็บ SQL query log เมื่อ DB_LOG = true
 * ไฟล์จะถูกสร้างใต้ ROOT_PATH
 *
 * @var string
 */
define('DB_LOG_FILE', DATA_FOLDER.'logs/sql_log.php');
/**
 * จำนวนวันเก็บ SQL query log
 *
 * @var int
 */
define('DB_LOG_RETENTION_DAYS', 7);
/**
 * เปิด/ปิดการใช้งาน Query Cache สำหรับ Database
 * true  = เปิดใช้งาน cache (แนะนำสำหรับ production)
 * false = ปิดใช้งาน cache (สำหรับ development)
 *
 * @var bool
 */
define('DB_CACHE', true);
/**
 * ประเภท Cache Driver
 * file   = เก็บ cache ลงไฟล์ (default)
 * memory = เก็บ cache ใน memory (หายเมื่อ request จบ)
 * redis  = เก็บ cache ใน Redis server
 *
 * @var string
 */
define('CACHE_DRIVER', 'file');
/**
 * ภาษาเริ่มต้น
 * auto = อัตโนมัติจากบราวเซอร์
 * th, en ตามภาษาที่เลือก
 *
 * @var string
 */
define('INIT_LANGUAGE', 'th');
/**
 * เปิด/ปิดการใช้งาน Session บน Database
 * ต้องติดตั้งตาราง sessions ด้วย
 *
 * @var bool
 */
define('USE_SESSION_DATABASE', false);
/**
 * IP ของ reverse proxy / load balancer ที่อยู่หน้าเว็บนี้ (คั่นด้วย ,)
 * ระบบจะเชื่อ X-Forwarded-For / X-Real-IP เฉพาะเมื่อคำขอมาจาก IP ในรายการนี้
 * ไม่ตั้ง = ใช้ REMOTE_ADDR เป็น IP ของผู้ใช้เสมอ (ปลอม header เพื่อหลบ rate limit / api_ips ไม่ได้)
 *
 * @var string
 */
define('TRUSTED_PROXIES', '');
/*
 * ระบุ SQL Mode ที่ต้องการ
 * หากพบปัญหาการใช้งาน
 *
 * @var string
 */
//define('SQL_MODE', '');
/**
 * load Kotchasan
 */
include 'Kotchasan/load.php';

/**
 * Security Headers (Phase 5)
 * Applied globally to all responses
 */
if (!headers_sent()) {
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    // Prevent clickjacking — allow same-origin framing only
    header('X-Frame-Options: SAMEORIGIN');
    // Control referrer information sent with requests
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Restrict permissions (camera, microphone, geolocation, etc.)
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // XSS protection (legacy browsers)
    header('X-XSS-Protection: 1; mode=block');

    // Content Security Policy
    // NOTE: 'unsafe-inline' is currently required for script/style because the
    // CMS relies on inline scripts, inline event handlers and inline styles in
    // its templates. This still adds meaningful defence (object-src/base-uri/
    // form-action lockdown, frame-ancestors, scheme restrictions). Tightening to
    // a nonce/hash-based policy is a follow-up that requires template changes.
    // ระบบนี้เป็น CMS สำหรับเว็บสาธารณะ ไม่ใช่แอปภายในแบบ adminframework — ธีมและ
    // เนื้อหาที่ผู้ดูแลแต่ละไซต์ใส่เองต้องพึ่งบริการภายนอกได้:
    //   - Google Fonts ใน themes/*/index.html แทบทุกธีม
    //   - Leaflet จาก unpkg.com (แผนที่ในหน้า about ของธีม)
    //   - iframe ที่ผู้ใช้ฝังผ่าน editor (IframePlugin/VideoPlugin: YouTube, Vimeo,
    //     Google Maps, Facebook page plugin, OpenStreetMap …) — ปลั๊กอินยอมเฉพาะ https://
    //     จึงเปิด frame-src เป็น https: ทั้งหมดแทนการไล่รายชื่อโฮสต์ที่ไม่มีวันครบ
    //   - <video src="https://…"> จาก VideoPlugin (media-src)
    //   - Google AdSense (google_ads_code) และ Google Tag/Analytics (google_tag) ที่
    //     index controller ใส่ใน <head>: adsbygoogle.js โหลดสคริปต์ต่อจาก
    //     googlesyndication / adtrafficquality.google / fundingchoicesmessages.google.com /
    //     doubleclick และยิง fetch กลับไปโฮสต์เดียวกัน — ตัวโฆษณาอยู่ใน iframe (frame-src https:)
    // frame-ancestors ยังเป็น 'self' — ใครฝังเราได้ต่างจากเราฝังใครได้
    $googleAds = 'https://*.googlesyndication.com https://*.adtrafficquality.google https://*.doubleclick.net https://*.google.com https://*.gstatic.com https://*.googleadservices.com https://*.googletagmanager.com https://*.google-analytics.com';
    $csp = implode('; ', array(
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://accounts.google.com https://apis.google.com https://connect.facebook.net https://telegram.org https://*.telegram.org https://unpkg.com ".$googleAds,
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com",
        "font-src 'self' data: https://fonts.gstatic.com",
        "img-src 'self' data: blob: https:",
        "media-src 'self' data: blob: https:",
        "connect-src 'self' https://accounts.google.com https://www.googleapis.com https://*.analytics.google.com ".$googleAds,
        // Social-login popups + ทุก iframe https ที่ผู้ดูแลไซต์ฝังผ่าน editor
        "frame-src 'self' https:",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'"
    ));
    header('Content-Security-Policy: '.$csp);
}
