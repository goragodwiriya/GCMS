<?php
/**
 * @filesource tests/LoginAttemptTest.php
 *
 * Brute-force lockout (Gcms\LoginAttempt) and its use by the login model.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Tests;

use Gcms\LoginAttempt;

class LoginAttemptTest extends TestCase
{
    protected function setUp(): void
    {
        $this->useDatabase(['login_attempt']);
        $this->useConfig([
            'max_login_attempts' => 3,
            'lockout_duration' => 15
        ]);
    }

    private function fail3($username, $ip)
    {
        for ($i = 0; $i < 3; $i++) {
            LoginAttempt::record($username, $ip, 'phpunit');
        }
    }

    private function rows()
    {
        return (int) \Kotchasan\Model::createQuery()->selectCount()->from('login_attempt')->first()->count;
    }

    public function testBelowThresholdIsNotLocked()
    {
        LoginAttempt::record('user', '10.0.0.1');
        LoginAttempt::record('user', '10.0.0.1');

        $this->assertFalse(LoginAttempt::isLocked('user', '10.0.0.1'));
        $this->assertSame(0, LoginAttempt::getRemainingLockTime('user', '10.0.0.1'));
    }

    public function testUsernameIsLockedFromAnyIp()
    {
        $this->fail3('user', '10.0.0.1');

        $this->assertTrue(LoginAttempt::isLocked('user', '10.9.9.9'));
        $this->assertFalse(LoginAttempt::isLocked('someone-else', '10.9.9.9'));
    }

    public function testIpIsLockedForAnyUsername()
    {
        $this->fail3('user', '10.0.0.1');

        $this->assertTrue(LoginAttempt::isLocked('someone-else', '10.0.0.1'));
    }

    public function testCaseAndWhitespaceVariantsShareOneCounter()
    {
        LoginAttempt::record('User', '10.0.0.1');
        LoginAttempt::record(' user ', '10.0.0.2');
        LoginAttempt::record('USER', '10.0.0.3');

        $this->assertTrue(LoginAttempt::isLocked('user', '10.0.0.4'));
    }

    public function testLoopbackAddressesShareOneCounter()
    {
        LoginAttempt::record('a', '::1');
        LoginAttempt::record('b', '127.0.0.1');
        LoginAttempt::record('c', 'localhost');

        $this->assertTrue(LoginAttempt::isLocked('d', '127.0.0.1'));
    }

    public function testRemainingTimeIsTheLockoutWindow()
    {
        $this->fail3('user', '10.0.0.1');

        $remaining = LoginAttempt::getRemainingLockTime('user', '10.0.0.1');
        $this->assertGreaterThan(15 * 60 - 5, $remaining);
        $this->assertLessThanOrEqual(15 * 60, $remaining);
    }

    public function testAttemptsOlderThanTheWindowDoNotCount()
    {
        $old = date('Y-m-d H:i:s', time() - 16 * 60);
        for ($i = 0; $i < 3; $i++) {
            $this->sql(
                'INSERT INTO {prefix}_login_attempt (username, ip_address, attempted_at) VALUES (?, ?, ?)',
                ['user', '10.0.0.1', $old]
            );
        }

        $this->assertFalse(LoginAttempt::isLocked('user', '10.0.0.1'));
        // isLocked() purges the aged-out rows for the scopes it checked
        $this->assertSame(0, $this->rows());
    }

    public function testSuccessfulLoginClearsUsernameAndIpCounters()
    {
        $this->fail3('user', '10.0.0.1');
        LoginAttempt::clear('USER', '10.0.0.1');

        $this->assertFalse(LoginAttempt::isLocked('user', '10.0.0.1'));
        $this->assertSame(0, $this->rows());
    }

    public function testEmptyUsernameAndIpRecordNothing()
    {
        LoginAttempt::record('', '');

        $this->assertSame(0, $this->rows());
    }

    public function testFailsClosedWhenTheAttemptTableIsMissing()
    {
        $this->sql('DROP TABLE {prefix}_login_attempt');

        $this->assertTrue(LoginAttempt::isLocked('user', '10.0.0.1'));
    }

    public function testAuthModelRateLimitReportsRetryAfter()
    {
        $this->fail3('user', '10.0.0.1');

        $result = \Index\Auth\Model::checkRateLimit('user', '10.0.0.1');
        $this->assertFalse($result['allowed']);
        $this->assertGreaterThan(0, $result['retry_after']);

        $this->assertTrue(\Index\Auth\Model::checkRateLimit('other', '10.0.0.2')['allowed']);
    }
}
