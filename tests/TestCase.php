<?php
/**
 * @filesource tests/TestCase.php
 *
 * Shared fixtures for the Gcms security suite.
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Table prefix used by the in-memory database, as the installer's {prefix}.
     */
    const PREFIX = 'gcms';

    /**
     * SQLite versions of the core tables the suite touches.
     * Columns follow install/core.sql; types are loosened to what SQLite needs.
     *
     * @var array<string, string>
     */
    private static $schema = [
        'login_attempt' => "CREATE TABLE {table} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL DEFAULT '',
            ip_address TEXT NOT NULL DEFAULT '',
            user_agent TEXT NOT NULL DEFAULT '',
            attempted_at TEXT NOT NULL
        )",
        'user' => "CREATE TABLE {table} (
            id INTEGER PRIMARY KEY,
            username TEXT,
            salt TEXT NOT NULL DEFAULT '',
            password TEXT NOT NULL DEFAULT '',
            status INTEGER DEFAULT 0,
            permission TEXT,
            name TEXT NOT NULL DEFAULT '',
            active INTEGER DEFAULT 1,
            activatecode TEXT,
            social TEXT DEFAULT 'user'
        )",
        'user_meta' => "CREATE TABLE {table} (
            value TEXT NOT NULL,
            name TEXT NOT NULL,
            member_id INTEGER NOT NULL
        )",
        'user_session' => "CREATE TABLE {table} (
            sid TEXT PRIMARY KEY,
            member_id INTEGER NOT NULL,
            expires_at INTEGER NOT NULL DEFAULT 0,
            ip TEXT,
            user_agent TEXT,
            fingerprint TEXT,
            last_event TEXT,
            last_seen TEXT
        )"
    ];

    /**
     * Point Kotchasan at a fresh in-memory SQLite database holding $tables.
     *
     * @param string[] $tables Keys of self::$schema
     *
     * @return \Kotchasan\Database
     */
    protected function useDatabase(array $tables)
    {
        \Kotchasan\Database::reset();
        \Kotchasan\Database::config([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => self::PREFIX
            ]
        ]);
        $db = \Kotchasan\Database::create();
        foreach ($tables as $name) {
            $db->raw(str_replace('{table}', self::PREFIX.'_'.$name, self::$schema[$name]));
        }

        return $db;
    }

    /**
     * Replace the site config (self::$cfg) that every KBase subclass reads.
     *
     * @param array $values
     *
     * @return object
     */
    protected function useConfig(array $values)
    {
        $cfg = (object) $values;
        (new \ReflectionProperty(\Kotchasan\KBase::class, 'cfg'))->setValue(null, $cfg);

        return $cfg;
    }

    /**
     * Run one raw statement on the current test database.
     *
     * @param string $sql    SQL with {prefix} for the table prefix
     * @param array  $values Bound values
     */
    protected function sql($sql, array $values = [])
    {
        return \Kotchasan\Database::create()->raw(str_replace('{prefix}', self::PREFIX, $sql), $values);
    }

    /**
     * Remove the throwaway DATA_FOLDER (revoked-token store, logs) between tests.
     */
    protected function tearDown(): void
    {
        $dir = ROOT_PATH.DATA_FOLDER;
        if (is_dir($dir)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($dir);
        }
        parent::tearDown();
    }
}
