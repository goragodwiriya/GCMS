<?php
/**
 * @filesource tests/WebLoginTokenTest.php
 *
 * Bearer/cookie token login on the public site (Web\Login::create()), which
 * every front-end page runs. It must accept exactly the tokens the API accepts
 * (Index\Auth\Model::getUserByToken()).
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Tests;

use Kotchasan\Http\Request;
use Kotchasan\Jwt;

class WebLoginTokenTest extends TestCase
{
    const SECRET = 'a-test-secret-that-is-long-enough-000';

    protected function setUp(): void
    {
        $this->useDatabase(['user', 'user_meta', 'user_session']);
        $this->useConfig([
            'jwt_secret' => self::SECRET,
            'password_key' => 'phpunit'
        ]);
        $this->sql("INSERT INTO {prefix}_user (id, username, name, status, active, permission) VALUES (2, 'member@example.com', 'Member', 0, 1, '')");
        $this->sql('INSERT INTO {prefix}_user_session (sid, member_id, expires_at) VALUES (?, 2, ?)', ['open-session', time() + 3600]);
    }

    /**
     * A token shaped like Index\Auth\Model::generateTokens() issues.
     */
    private function token(array $claims = [])
    {
        return Jwt::encode(array_merge([
            'sub' => 2,
            'iat' => time(),
            'exp' => time() + 3600,
            'type' => 'access',
            'jti' => bin2hex(random_bytes(8)),
            'sid' => 'open-session'
        ], $claims), self::SECRET);
    }

    /**
     * Run the front-end login check with $token as a Bearer header.
     *
     * @return object|null the logged-in member
     */
    private function loginWith($token)
    {
        \Web\Login::create((new Request(false))->withHeader('Authorization', 'Bearer '.$token));

        return \Web\Login::isMember();
    }

    public function testAccessTokenForAnOpenSessionLogsIn()
    {
        $login = $this->loginWith($this->token());

        $this->assertNotNull($login);
        $this->assertSame(2, $login->id);
        $this->assertSame('member@example.com', $login->username);
    }

    public function testRefreshTokenIsNotALogin()
    {
        $this->assertNull($this->loginWith($this->token(['type' => 'refresh', 'exp' => time() + 604800])));
    }

    public function testTokenFromAClosedSessionIsRejected()
    {
        // Index\Auth\Model::logoutAllSessions() — password change, "log out of all devices"
        $this->sql('DELETE FROM {prefix}_user_session WHERE member_id = 2');

        $this->assertNull($this->loginWith($this->token()));
    }

    public function testSuspendedAccountIsRejected()
    {
        $this->sql('UPDATE {prefix}_user SET active = 0 WHERE id = 2');

        $this->assertNull($this->loginWith($this->token()));
    }

    public function testExpiredTokenIsRejected()
    {
        $this->assertNull($this->loginWith($this->token(['iat' => time() - 7200, 'exp' => time() - 3600])));
    }

    public function testTokenSignedWithAnotherSecretIsRejected()
    {
        $forged = Jwt::encode(['sub' => 2, 'exp' => time() + 3600, 'type' => 'access', 'sid' => 'open-session'], 'some-other-secret-that-is-long-enough');

        $this->assertNull($this->loginWith($forged));
    }

    public function testMalformedHeaderIsIgnored()
    {
        $this->assertNull($this->loginWith("x' OR '1'='1"));
    }

    public function testTheApiGateAgreesWithTheSite()
    {
        $this->assertNotNull(\Index\Auth\Model::getUserByToken($this->token()));
        $this->assertNull(\Index\Auth\Model::getUserByToken($this->token(['type' => 'refresh'])));
        $this->assertNull(\Index\Auth\Model::getUserByToken($this->token(['sid' => 'closed-session'])));
    }
}
