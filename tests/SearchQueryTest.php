<?php

/*
 * This file is part of fab2s/laravel-dt0.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/laravel-dt0
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Tests;

use fab2s\Searchable\SearchQuery;
use Illuminate\Database\Eloquent\Builder;

class SearchQueryTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'mysql_fake');
        $app['config']->set('database.connections.mysql_fake', [
            'driver'   => 'mysql',
            'host'     => '',
            'database' => '',
            'username' => '',
            'password' => '',
        ]);
        $app['config']->set('database.connections.pgsql_fake', [
            'driver'   => 'pgsql',
            'host'     => '',
            'database' => '',
            'username' => '',
            'password' => '',
        ]);
    }

    private function queryForDriver(string $connection = 'mysql_fake'): Builder
    {
        $model = new Model;
        $model->setConnection($connection);

        return $model->newQuery();
    }

    public function test_get_order(): void
    {
        $sq = new SearchQuery;

        $this->assertSame('DESC', $sq->getOrder('DESC'));
        $this->assertSame('ASC', $sq->getOrder('ASC'));
        $this->assertSame('', $sq->getOrder('INVALID'));
        $this->assertSame('', $sq->getOrder(null));
        $this->assertSame('', $sq->getOrder(''));
    }

    public function test_add_match_mysql(): void
    {
        $sq    = new SearchQuery('DESC');
        $query = $this->queryForDriver();
        $sq->addMatch($query, 'hello world');

        $sql      = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('MATCH', $sql);
        $this->assertStringContainsString('AGAINST', $sql);
        $this->assertStringContainsString('BOOLEAN MODE', $sql);
        $this->assertStringContainsString('DESC', $sql);
        $this->assertSame(['hello* world*', 'hello* world*'], $bindings);
    }

    public function test_add_match_pgsql(): void
    {
        $sq    = new SearchQuery('DESC');
        $query = $this->queryForDriver('pgsql_fake');
        $sq->addMatch($query, 'hello world');

        $sql      = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('to_tsvector', $sql);
        $this->assertStringContainsString('to_tsquery', $sql);
        $this->assertStringContainsString('ts_rank', $sql);
        $this->assertStringContainsString('DESC', $sql);
        $this->assertSame(['hello:* & world:*', 'hello:* & world:*'], $bindings);
    }

    public function test_add_match_empty_terms(): void
    {
        $sq    = new SearchQuery;
        $query = $this->queryForDriver();
        $sq->addMatch($query, '');

        $this->assertEmpty($query->getQuery()->wheres);
        $this->assertEmpty($query->getQuery()->orders);
        $this->assertEmpty($query->getBindings());
    }

    public function test_add_match_with_table_alias(): void
    {
        $sq    = new SearchQuery('DESC');
        $query = $this->queryForDriver();
        $sq->addMatch($query, 'hello', 'alias');

        $this->assertStringContainsString('alias.searchable', $query->toSql());
    }

    public function test_add_match_without_order(): void
    {
        $sq    = new SearchQuery(null);
        $query = $this->queryForDriver();
        $sq->addMatch($query, 'hello');

        $sql = $query->toSql();

        $this->assertStringContainsString('MATCH', $sql);
        $this->assertSame(1, substr_count($sql, 'MATCH'));
        $this->assertEmpty($query->getQuery()->orders);
        $this->assertSame(['hello*'], $query->getBindings());
    }

    public function test_add_match_with_explicit_order(): void
    {
        $sq    = new SearchQuery(null);
        $query = $this->queryForDriver();
        $sq->addMatch($query, 'hello', '', 'ASC');

        $sql = $query->toSql();

        $this->assertSame(2, substr_count($sql, 'MATCH'));
        $this->assertStringContainsString('ASC', $sql);
    }

    public function test_add_match_phonetic_mysql(): void
    {
        $sq    = new SearchQuery('DESC', SearchQuery::SEARCHABLE_FIELD, 'english', true);
        $query = $this->queryForDriver();
        $sq->addMatch($query, 'john');

        $this->assertSame(['john* jn*', 'john* jn*'], $query->getBindings());
    }

    public function test_add_match_phonetic_pgsql(): void
    {
        $sq    = new SearchQuery('DESC', SearchQuery::SEARCHABLE_FIELD, 'english', true);
        $query = $this->queryForDriver('pgsql_fake');
        $sq->addMatch($query, 'john');

        $this->assertSame(['john:* & jn:*', 'john:* & jn:*'], $query->getBindings());
    }

    public function test_add_match_pgsql_custom_ts_config(): void
    {
        $sq    = new SearchQuery('DESC', SearchQuery::SEARCHABLE_FIELD, 'french');
        $query = $this->queryForDriver('pgsql_fake');
        $sq->addMatch($query, 'bonjour');

        $sql = $query->toSql();

        $this->assertStringContainsString("'french'", $sql);
        $this->assertStringNotContainsString("'english'", $sql);
    }

    public function test_scope_search_mysql(): void
    {
        $query = $this->queryForDriver();
        $query->search('hello world'); // @phpstan-ignore method.notFound

        $sql = $query->toSql();

        $this->assertStringContainsString('MATCH', $sql);
        $this->assertStringContainsString('AGAINST', $sql);
        $this->assertStringContainsString('DESC', $sql);
        $this->assertSame(['hello* world*', 'hello* world*'], $query->getBindings());
    }

    public function test_scope_search_pgsql(): void
    {
        $query = $this->queryForDriver('pgsql_fake');
        $query->search('hello world'); // @phpstan-ignore method.notFound

        $sql = $query->toSql();

        $this->assertStringContainsString('to_tsvector', $sql);
        $this->assertStringContainsString('ts_rank', $sql);
        $this->assertSame(['hello:* & world:*', 'hello:* & world:*'], $query->getBindings());
    }

    public function test_scope_search_without_order(): void
    {
        $query = $this->queryForDriver();
        $query->search('hello', null); // @phpstan-ignore method.notFound

        $this->assertSame(1, substr_count($query->toSql(), 'MATCH'));
        $this->assertSame(['hello*'], $query->getBindings());
    }

    public function test_scope_search_phonetic(): void
    {
        $model = new PhoneticModel;
        $model->setConnection('mysql_fake');
        $query = $model->newQuery();
        $query->search('john'); // @phpstan-ignore method.notFound

        $this->assertSame(['john* jn*', 'john* jn*'], $query->getBindings());
    }
}
