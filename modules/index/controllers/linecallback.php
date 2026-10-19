<?php
/**
 * @filesource modules/index/controllers/linecallback.php
 *
 * @copyright 2024 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Linecallback;

use Gcms\Api as ApiController;
use Kotchasan\Curl;
use Kotchasan\Http\Request;

/**
 * linecallback.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * Controller รับค่าการ Login ด้วย LINE
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $code = $request->get('code', '')->toString();
        $ret_url = base64_decode($request->get('state', '')->toString());
        // state มาจาก URL ที่ใครก็สร้างได้ — กลับไปได้เฉพาะหน้าในเว็บนี้
        if (strpos($ret_url, WEB_URL) !== 0) {
            $ret_url = WEB_URL;
        }

        if ($code !== '') {
            // get refresh token
            $url = "https://api.line.me/oauth2/v2.1/token";
            $curl = new Curl();
            $content = $curl->post($url, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                // ต้องตรงกับ redirect_uri ที่ SocialLogin.js ส่งให้ LINE ทุกตัวอักษร = callback ของโฮสต์ที่ผู้ใช้อยู่
                // เดิมตัด www. ออก ผู้ที่เข้าจาก www.example.com จึงเข้าระบบไม่ได้ และต่อให้ใช้ callback ไม่มี www
                // ทั้งสองขา cookie ที่ตั้งบน example.com ก็ไม่ถูกส่งไปที่ www.example.com — ไซต์ที่เปิดได้ทั้งสองโฮสต์
                // ต้องลงทะเบียน Callback URL ทั้งสองแบบไว้ใน LINE Developers Console (ใส่ได้หลาย URL บรรทัดละหนึ่ง)
                'redirect_uri' => WEB_URL.'line/callback.php',
                'client_id' => self::$cfg->line_channel_id,
                'client_secret' => self::$cfg->line_channel_secret
            ]);
            $result = json_decode($content, true);
            // get user info
            $url = 'https://api.line.me/oauth2/v2.1/verify';
            $curl = new Curl();
            $content = $curl->post($url, [
                'id_token' => $result['id_token'],
                'client_id' => self::$cfg->line_channel_id
            ]);
            $user = json_decode($content, true);
            if (!empty($user['sub'])) {
                $data = [
                    'username' => empty($user['email']) ? 'LINE'.$user['sub'] : $user['email'],
                    'name' => $user['name'] ?? '',
                    'picture' => $user['picture'] ?? '',
                    'line_uid' => $user['sub'],
                    'social' => 'line'
                ];

                $result = \Index\Social\Model::authenticate(
                    $data,
                    $ret_url,
                    'LINE',
                    $request->getClientIp()
                );
                if (!empty($result['success']) && !empty($result['token'])) {
                    \Index\Auth\Model::setCookie('auth_token', $result['token']);
                }

                header('Location: '.($ret_url !== '' ? $ret_url : WEB_URL));
                exit;
            }
        }
        // redirect
        header('Location: '.WEB_URL);
        exit;
    }
}
