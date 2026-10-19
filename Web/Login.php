<?php
/**
 * @filesource Web/Login.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Web;

use Kotchasan\Http\Request;

/**
 * คลาสสำหรับตรวจสอบการ Login
 * รองรับทั้ง Session-based และ JWT Token-based authentication
 *
 * Security Features:
 * - HMAC-SHA256 signature verification
 * - Timing-safe comparison (hash_equals)
 * - Token expiration (exp) validation
 * - Issued at (iat) validation
 * - Not before (nbf) validation
 * - Token revocation support
 * - Payload type validation
 * - Minimum secret key length enforcement
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Login extends \Kotchasan\Login
{
    /**
     * Minimum secret key length (32 bytes = 256 bits)
     */
    const MIN_SECRET_LENGTH = 32;

    /**
     * Maximum token age in seconds (prevent replay with very old tokens)
     */
    const MAX_TOKEN_AGE = 86400 * 30; // 30 days

    /**
     * Clock skew tolerance in seconds
     */
    const CLOCK_SKEW = 60;

    /**
     * ข้อมูล login จาก JWT Token
     *
     * @var object|null
     */
    protected static $jwtLogin = null;

    /**
     * JWT Payload จาก request
     *
     * @var array|null
     */
    protected static $jwtPayload = null;

    /**
     * Validates the login request and performs the login process.
     * ตรวจสอบทั้ง JWT Token และ Session
     *
     * @param Request $request The HTTP request object.
     * @return static
     */
    public static function create(Request $request)
    {
        $obj = new static();

        // Reset previous login state
        self::$jwtLogin = null;
        self::$jwtPayload = null;

        // 1. ตรวจสอบ JWT Token ก่อน (จาก JwtMiddleware)
        $jwtPayload = $request->getAttribute('jwt_payload');

        if ($jwtPayload && $obj->validatePayload($jwtPayload)) {
            // JWT Token valid - แปลง payload เป็น login object
            $login = $obj->createLoginFromJwt($jwtPayload);
            if ($login) {
                self::$jwtPayload = $jwtPayload;
                self::$jwtLogin = $login;
                return $obj;
            }
        }

        // 2. ถ้าไม่มี JWT ให้ลองดึงจาก Authorization header หรือ Cookie
        $token = $obj->extractToken($request);

        if ($token) {
            $payload = $obj->verifyToken($token);
            if ($payload && $obj->validatePayload($payload)) {
                // ตรวจสอบ token revocation
                if (!$obj->isTokenRevoked($payload)) {
                    $login = $obj->createLoginFromJwt($payload);
                    if ($login) {
                        self::$jwtPayload = $payload;
                        self::$jwtLogin = $login;
                        return $obj;
                    }
                }
            }
        }

        // 3. Fallback ไปใช้ Session-based login (parent class)
        return parent::create($request);
    }

    /**
     * ดึง Token จาก Authorization header หรือ Cookie
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractToken(Request $request)
    {
        // Try Authorization header first (more secure)
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader)) {
            // Strict Bearer token pattern
            if (preg_match('/^Bearer\s+([A-Za-z0-9\-_\.]+)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        // Try Cookie (only if configured)
        $cookieName = self::$cfg->jwt_cookie_name ?? 'auth_token';
        if (!empty($cookieName)) {
            $token = $request->cookie($cookieName)->filter('a-zA-Z0-9\-_\.');
            if (!empty($token)) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Verify JWT Token
     *
     * @param string $token
     * @return array|null
     */
    protected function verifyToken($token)
    {
        // Validate token format first
        if (!$this->isValidTokenFormat($token)) {
            return null;
        }

        // ใช้ Model ในการ verify token (ถ้ามี)
        if (class_exists('\Index\Auth\Model') && method_exists('\Index\Auth\Model', 'verifyToken')) {
            return \Index\Auth\Model::verifyToken($token);
        }

        // Fallback: verify ด้วยตัวเอง
        return $this->verifyTokenInternal($token);
    }

    /**
     * Validate token format before processing
     *
     * @param string $token
     * @return bool
     */
    protected function isValidTokenFormat($token)
    {
        if (empty($token) || !is_string($token)) {
            return false;
        }

        // Length check (prevent DoS with very long tokens)
        if (strlen($token) > 4096) {
            return false;
        }

        // Must contain only valid characters
        if (!preg_match('/^[A-Za-z0-9\-_\.]+$/', $token)) {
            return false;
        }

        return true;
    }

    /**
     * Internal token verification with enhanced security
     *
     * @param string $token
     * @return array|null
     */
    protected function verifyTokenInternal($token)
    {
        $parts = explode('.', $token);

        // Support both 2-part (custom) and 3-part (standard JWT) formats
        if (count($parts) === 2) {
            return $this->verifyCustomToken($parts[0], $parts[1]);
        } elseif (count($parts) === 3) {
            return $this->verifyStandardJwt($parts[0], $parts[1], $parts[2]);
        }

        return null;
    }

    /**
     * Verify custom 2-part token (payload.signature)
     *
     * @param string $payloadBase64
     * @param string $signature
     * @return array|null
     */
    protected function verifyCustomToken($payloadBase64, $signature)
    {
        $secret = $this->getSecretKey();
        if ($secret === null) {
            return null;
        }

        // Verify signature with timing-safe comparison
        $expectedSignature = hash_hmac('sha256', $payloadBase64, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        return $this->decodeAndValidatePayload($payloadBase64);
    }

    /**
     * Verify standard 3-part JWT (header.payload.signature)
     *
     * @param string $headerBase64
     * @param string $payloadBase64
     * @param string $signatureBase64
     * @return array|null
     */
    protected function verifyStandardJwt($headerBase64, $payloadBase64, $signatureBase64)
    {
        // Decode header
        $headerJson = $this->base64UrlDecode($headerBase64);
        $header = json_decode($headerJson, true);

        if (!$header || !isset($header['alg'])) {
            return null;
        }

        // CRITICAL: Only accept HS256, reject "none" algorithm
        $allowedAlgorithms = ['HS256'];
        if (!in_array($header['alg'], $allowedAlgorithms, true)) {
            return null;
        }

        $secret = $this->getSecretKey();
        if ($secret === null) {
            return null;
        }

        // Verify signature
        $data = $headerBase64.'.'.$payloadBase64;
        $signature = $this->base64UrlDecode($signatureBase64);
        $expectedSignature = hash_hmac('sha256', $data, $secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        return $this->decodeAndValidatePayload($payloadBase64);
    }

    /**
     * Decode payload and perform time-based validations
     *
     * @param string $payloadBase64
     * @return array|null
     */
    protected function decodeAndValidatePayload($payloadBase64)
    {
        $payloadJson = $this->base64UrlDecode($payloadBase64);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        $now = time();

        // Required fields
        if (!isset($payload['sub']) || !isset($payload['exp'])) {
            return null;
        }

        // Check expiration (with clock skew tolerance)
        if ($payload['exp'] < ($now - self::CLOCK_SKEW)) {
            return null;
        }

        // Check not before (nbf) if present
        if (isset($payload['nbf']) && $payload['nbf'] > ($now + self::CLOCK_SKEW)) {
            return null;
        }

        // Check issued at (iat) if present - prevent tokens from the future
        if (isset($payload['iat'])) {
            // Token can't be issued in the future
            if ($payload['iat'] > ($now + self::CLOCK_SKEW)) {
                return null;
            }
            // Token can't be too old (prevent replay with ancient tokens)
            if ($payload['iat'] < ($now - self::MAX_TOKEN_AGE)) {
                return null;
            }
        }

        // Optional: Verify issuer if configured
        if (!empty(self::$cfg->jwt_issuer) && isset($payload['iss'])) {
            if ($payload['iss'] !== self::$cfg->jwt_issuer) {
                return null;
            }
        }

        // Optional: Verify audience if configured
        if (!empty(self::$cfg->jwt_audience) && isset($payload['aud'])) {
            $expectedAud = self::$cfg->jwt_audience;
            $tokenAud = is_array($payload['aud']) ? $payload['aud'] : [$payload['aud']];
            if (!in_array($expectedAud, $tokenAud, true)) {
                return null;
            }
        }

        return $payload;
    }

    /**
     * Validate payload data types and values
     *
     * @param array $payload
     * @return bool
     */
    protected function validatePayload(array $payload)
    {
        // sub (user ID) must be a positive integer
        if (!isset($payload['sub'])) {
            return false;
        }
        $sub = $payload['sub'];
        if (!is_int($sub) && !ctype_digit((string) $sub)) {
            return false;
        }
        if ((int) $sub <= 0) {
            return false;
        }

        // status must be an integer if present
        if (isset($payload['status'])) {
            $status = $payload['status'];
            if (!is_int($status) && !ctype_digit((string) $status)) {
                return false;
            }
        }

        // exp must be a positive integer
        if (!isset($payload['exp']) || !is_int($payload['exp']) || $payload['exp'] <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Check if token is revoked
     * Override this method to implement token blacklist/revocation
     *
     * @param array $payload
     * @return bool
     */
    protected function isTokenRevoked(array $payload)
    {
        // Check token version if user has a minimum token version
        if (isset($payload['sub']) && isset($payload['ver'])) {
            // ถ้ามี Model สำหรับตรวจสอบ version
            if (class_exists('\Index\Auth\Model') && method_exists('\Index\Auth\Model', 'getMinTokenVersion')) {
                $minVersion = \Index\Auth\Model::getMinTokenVersion($payload['sub']);
                if ($minVersion !== null && $payload['ver'] < $minVersion) {
                    return true; // Token is revoked
                }
            }
        }

        // Check token ID (jti) against blacklist if implemented
        if (isset($payload['jti'])) {
            if (class_exists('\Index\Auth\Model') && method_exists('\Index\Auth\Model', 'isTokenBlacklisted')) {
                if (\Index\Auth\Model::isTokenBlacklisted($payload['jti'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get secret key with validation
     *
     * @return string|null
     */
    protected function getSecretKey()
    {
        $secret = self::$cfg->jwt_secret ?? self::$cfg->password_key ?? null;

        if (empty($secret)) {
            // Log security warning
            error_log('[SECURITY WARNING] JWT secret key is not configured');
            return null;
        }

        // Enforce minimum secret length
        if (strlen($secret) < self::MIN_SECRET_LENGTH) {
            error_log('[SECURITY WARNING] JWT secret key is too short (min: '.self::MIN_SECRET_LENGTH.' bytes)');
            return null;
        }

        return $secret;
    }

    /**
     * Base64 URL-safe decode
     *
     * @param string $input
     * @return string
     */
    protected function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * สร้าง login object จาก JWT payload
     * ถ้า payload ไม่มี status/permission จะดึงจาก database
     *
     * @param array $payload
     * @return object|null
     */
    protected function createLoginFromJwt(array $payload)
    {
        $userId = (int) ($payload['sub'] ?? 0);

        // ถ้า payload มีข้อมูลครบแล้ว ใช้ได้เลย
        if (isset($payload['status']) && isset($payload['username'])) {
            return (object) [
                'id' => $userId,
                'username' => $this->sanitizeString($payload['username'] ?? ''),
                'email' => $this->sanitizeString($payload['email'] ?? ''),
                'name' => $this->sanitizeString($payload['name'] ?? ''),
                'status' => (int) ($payload['status'] ?? 0),
                'permission' => $this->sanitizePermissions($payload['permission'] ?? []),
                'token' => $payload
            ];
        }

        // ถ้า payload ไม่มี status (เช่น token จาก /admin) ต้องดึงจาก database
        if ($userId > 0) {
            $user = $this->getUserFromDatabase($userId);
            if ($user) {
                return (object) [
                    'id' => (int) $user->id,
                    'username' => $this->sanitizeString($user->username ?? ''),
                    'email' => $this->sanitizeString($user->email ?? $user->username ?? ''),
                    'name' => $this->sanitizeString($user->name ?? ''),
                    'status' => (int) ($user->status ?? 0),
                    'permission' => $this->sanitizePermissions($user->permission ?? []),
                    'token' => $payload
                ];
            }
        }

        return null;
    }

    /**
     * ดึงข้อมูล user จาก database
     *
     * @param int $userId
     * @return object|null
     */
    protected function getUserFromDatabase($userId)
    {
        // ใช้ Auth Model ถ้ามี
        if (class_exists('\Index\Auth\Model') && method_exists('\Index\Auth\Model', 'getUserById')) {
            return \Index\Auth\Model::getUserById($userId);
        }

        // Fallback: query ตรงๆ
        try {
            $user = \Kotchasan\DB::create()->first('user', [['id', $userId]]);

            if ($user) {
                // Parse permission
                if (isset($user->permission) && is_string($user->permission)) {
                    $user->permission = empty($user->permission) ? [] : explode(',', trim($user->permission, ','));
                } elseif (!isset($user->permission)) {
                    $user->permission = [];
                }
            }

            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sanitize string from payload
     *
     * @param mixed $value
     * @return string
     */
    protected function sanitizeString($value)
    {
        if (!is_string($value)) {
            return '';
        }
        // Remove null bytes and trim
        return trim(str_replace("\0", '', $value));
    }

    /**
     * Sanitize permissions array
     *
     * @param mixed $permissions
     * @return array
     */
    protected function sanitizePermissions($permissions)
    {
        if (!is_array($permissions)) {
            return [];
        }

        $sanitized = [];
        foreach ($permissions as $perm) {
            if (is_string($perm) && preg_match('/^[a-zA-Z0-9_\-]+$/', $perm)) {
                $sanitized[] = $perm;
            }
        }
        return $sanitized;
    }

    /**
     * Checks if the user is a member (logged in).
     * ตรวจสอบจาก JWT ก่อน แล้วค่อย fallback ไป Session
     *
     * @return object|null Returns the login information if the user is a member, or null otherwise.
     */
    public static function isMember()
    {
        // ตรวจสอบ JWT login ก่อน
        if (self::$jwtLogin !== null) {
            return self::$jwtLogin;
        }

        // Fallback ไป Session
        return parent::isMember();
    }

    /**
     * Checks if the user is an admin.
     *
     * @param object|null $login The login information.
     *
     * @return object|null Returns the login information if the user is an admin, or null otherwise.
     */
    public static function isAdmin($login = null)
    {
        $login = $login ?? self::isMember();
        if (!$login || !isset($login->status)) {
            return null;
        }
        // Strict integer comparison
        return ((int) $login->status) === 1 ? $login : null;
    }

    /**
     * Check if the user is a super admin (id = 1)
     *
     * @param object|null $login The login information.
     *
     * @return object|null Returns the login information if the user is a super admin, or null otherwise.
     */
    public static function isSuperAdmin($login = null)
    {
        $login = $login ?? self::isMember();
        if (!$login || !isset($login->id)) {
            return null;
        }
        // Strict integer comparison
        return ((int) $login->id) === 1 ? $login : null;
    }

    /**
     * Check permission
     *
     * @param string|array $permission
     * @param object|null $login The login information.
     * @param bool $checkAdmin Check if you are an admin or not.
     *
     * @return object|null Returns the login information if the user has permission, or null otherwise.
     */
    public static function hasPermission($permission, $login = null, $checkAdmin = true)
    {
        $login = $login ?? self::isMember();

        if (!$login) {
            return null;
        }

        // Admin has all rights
        if ($checkAdmin && isset($login->status) && ((int) $login->status) === 1) {
            return $login;
        }

        // Check specific permission
        if (!empty($permission) && isset($login->permission) && is_array($login->permission)) {
            $checkPermissions = is_array($permission) ? $permission : [$permission];

            foreach ($checkPermissions as $perm) {
                if (is_string($perm) && in_array($perm, $login->permission, true)) {
                    return $login;
                }
            }
        }

        return null;
    }

    /**
     * Get JWT payload
     *
     * @return array|null
     */
    public static function getJwtPayload()
    {
        return self::$jwtPayload;
    }

    /**
     * Check if current login is from JWT
     *
     * @return bool
     */
    public static function isJwtLogin()
    {
        return self::$jwtLogin !== null;
    }

    /**
     * Logout - clear both JWT and Session
     *
     * @param Request $request The HTTP request object.
     * @return void
     */
    public function logout(Request $request)
    {
        // Clear JWT login
        self::$jwtLogin = null;
        self::$jwtPayload = null;

        // Clear Session (parent)
        parent::logout($request);
    }

    /**
     * Get user ID from current login
     *
     * @return int|null
     */
    public static function getUserId()
    {
        $login = self::isMember();
        return $login && isset($login->id) ? (int) $login->id : null;
    }

    /**
     * Get user status from current login
     *
     * @return int|null
     */
    public static function getStatus()
    {
        $login = self::isMember();
        return $login && isset($login->status) ? (int) $login->status : null;
    }
}
