<?php
/**
 * @filesource tests/QuerySafetyTest.php
 *
 * SQL injection guards: bound values in the query builder, and the sort
 * parameter that Gcms\Table passes straight to orderBy().
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 */

namespace Tests;

use Kotchasan\Model;

class QuerySafetyTest extends TestCase
{
    const INJECTION = "' OR '1'='1";

    protected function setUp(): void
    {
        $this->useDatabase(['user']);
        $this->sql("INSERT INTO {prefix}_user (id, username, name) VALUES (1, 'admin', 'Admin'), (2, 'bob', 'Bob')");
    }

    public function testWhereValuesAreBoundNotInlined()
    {
        $query = Model::createQuery()->select('id')->from('user')->where([['username', self::INJECTION]]);

        $this->assertStringNotContainsString(self::INJECTION, $query->toSql());
        $this->assertSame([], $query->fetchAll());
    }

    public function testInsertedValuesAreStoredLiterally()
    {
        $name = "Robert'); DROP TABLE gcms_user; --";
        \Kotchasan\DB::create()->insert('user', ['id' => 3, 'username' => 'robert', 'name' => $name]);

        $row = Model::createQuery()->select('name')->from('user')->where([['id', 3]])->first();
        $this->assertSame($name, $row->name);
        $this->assertSame(3, (int) Model::createQuery()->selectCount()->from('user')->first()->count);
    }

    public function testUpdateWhereValueCannotWidenTheMatch()
    {
        \Kotchasan\DB::create()->update('user', [['username', self::INJECTION]], ['name' => 'pwned']);

        $pwned = Model::createQuery()->selectCount()->from('user')->where([['name', 'pwned']])->first();
        $this->assertSame(0, (int) $pwned->count);
    }

    /**
     * @dataProvider hostileSorts
     */
    public function testTableSortDropsAnythingButColumnNames($sort)
    {
        $parsed = $this->sortParser()->parse($sort);

        $this->assertSame([], $parsed['columns']);
    }

    public static function hostileSorts()
    {
        return [
            'statement' => ['id; DROP TABLE gcms_user'],
            'comment' => ['id -- '],
            'subquery' => ['(SELECT password FROM gcms_user)'],
            'quoted' => ['`id`'],
            'bad direction' => ['id sideways']
        ];
    }

    public function testTableSortKeepsValidColumnsAndDirections()
    {
        $parsed = $this->sortParser()->parse('name desc, id');

        $this->assertSame(['name', 'id'], $parsed['columns']);
        $this->assertSame(['desc', 'asc'], $parsed['directions']);
    }

    public function testTableSortHonoursTheAllowList()
    {
        $parsed = $this->sortParser(['name'])->parse('password asc, name desc');

        $this->assertSame(['name'], $parsed['columns']);
    }

    /**
     * Gcms\Table::parseSort() is protected; expose it without running the controller.
     */
    private function sortParser(array $allowed = [])
    {
        $parser = (new \ReflectionClass(SortParser::class))->newInstanceWithoutConstructor();
        $parser->allow($allowed);

        return $parser;
    }
}

class SortParser extends \Gcms\Table
{
    public function allow(array $columns)
    {
        $this->allowedSortColumns = $columns;
    }

    public function parse($sort)
    {
        return $this->parseSort($sort);
    }
}
