<?php
/**
 * @filesource tests/bootstrap.php
 *
 * PHPUnit bootstrap for the Gcms security suite.
 *
 * Loads Kotchasan the same way load.php does, but without sending headers and
 * with DATA_FOLDER pointed at a throwaway folder, so tests never touch a real
 * site's datas/. Each test gets a fresh in-memory SQLite database from
 * Tests\TestCase::useDatabase().
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)).'/');
define('APP_PATH', ROOT_PATH);
define('BASE_PATH', '/');
define('DATA_FOLDER', 'tests/tmp/');
// 2 = report every error and leave PHP's handlers alone, so PHPUnit sees warnings itself
define('DEBUG', 2);
define('LOG_DESTINATION', 'LOG_SYSTEM');
define('DB_LOG', false);
define('DB_CACHE', false);
define('INIT_LANGUAGE', 'en');
define('TRUSTED_PROXIES', '');

include ROOT_PATH.'Kotchasan/load.php';

// The code under test reports swallowed failures through error_log(); keep them out of the test output
ini_set('error_log', sys_get_temp_dir().'/gcms-phpunit-error.log');

require_once __DIR__.'/TestCase.php';
