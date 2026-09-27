<?php
/**
 * @filesource tests/JwtTest.php
 *
 * Kotchasan\Jwt: the HS256 tokens the login model issues.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Tests;

use Kotchasan\Jwt;

class JwtTest extends TestCase
{
    const SECRET = 'a-test-secret-that-is-long-enough-000';

    private static function b64u($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function testRoundTrip()
    {
        $token = Jwt::encode(['sub' => 7, 'exp' => time() + 60], self::SECRET);

        $this->assertSame(7, Jwt::decode($token, self::SECRET)['sub']);
    }

    public function testWrongSecretIsRejected()
    {
        $token = Jwt::encode(['sub' => 7, 'exp' => time() + 60], self::SECRET);

        $this->assertNull(Jwt::decode($token, self::SECRET.'x'));
    }

    public function testTamperedPayloadIsRejected()
    {
        list($header, , $signature) = explode('.', Jwt::encode(['sub' => 7, 'exp' => time() + 60], self::SECRET));
        $forged = $header.'.'.self::b64u(json_encode(['sub' => 1, 'exp' => time() + 60])).'.'.$signature;

        $this->assertNull(Jwt::decode($forged, self::SECRET));
    }

    public function testAlgNoneIsRejected()
    {
        $unsigned = self::b64u(json_encode(['typ' => 'JWT', 'alg' => 'none'])).'.'
            .self::b64u(json_encode(['sub' => 1, 'exp' => time() + 60])).'.';

        $this->assertNull(Jwt::decode($unsigned, self::SECRET));
    }

    public function testAlgorithmOutsideTheAllowListIsRejected()
    {
        $token = Jwt::encode(['sub' => 7, 'exp' => time() + 60], self::SECRET, 'HS512');

        $this->assertNull(Jwt::decode($token, self::SECRET, ['HS256']));
    }

    public function testExpiredTokenIsRejected()
    {
        $token = Jwt::encode(['sub' => 7, 'exp' => time() - 1], self::SECRET);

        $this->assertNull(Jwt::decode($token, self::SECRET));
    }

    public function testNotBeforeInTheFutureIsRejected()
    {
        $token = Jwt::encode(['sub' => 7, 'nbf' => time() + 60, 'exp' => time() + 120], self::SECRET);

        $this->assertNull(Jwt::decode($token, self::SECRET));
    }

    public function testEmptySecretNeverVerifies()
    {
        $this->expectException(\InvalidArgumentException::class);
        Jwt::encode(['sub' => 7], '');
    }

    public function testEmptySecretDecodeReturnsNull()
    {
        $token = Jwt::encode(['sub' => 7, 'exp' => time() + 60], self::SECRET);

        $this->assertNull(Jwt::decode($token, ''));
    }
}
