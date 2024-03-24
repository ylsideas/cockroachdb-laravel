<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

use Illuminate\Database\Connection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use YlsIdeas\CockroachDb\Builder\CockroachDbBuilder;
use YlsIdeas\CockroachDb\Processor\CockroachDbProcessor;
use YlsIdeas\CockroachDb\Schema\CockroachDbGrammar;
use YlsIdeas\CockroachDb\Tests\WithMultipleApplicationVersions;

class DatabaseCockroachDbBuilderTest extends TestCase
{
    use m\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use WithMultipleApplicationVersions;

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_create_database()
    {
        $grammar = new CockroachDbGrammar();

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'create database "my_temporary_database" encoding "utf8"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function test_drop_database_if_exists()
    {
        $grammar = new CockroachDbGrammar();

        $connection = $this->getConnection();
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'drop database if exists "my_database_a"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }

    public function test_has_table_when_schema_unqualified_and_search_path_missing()
    {
        $this->skipIfOlderThan('10.0.0');
        
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(null);
        $grammar = m::mock(CockroachDbGrammar::class);
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileTableExists')->andReturn("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'");
        $connection->shouldReceive('selectFromWriteConnection')->with("select * from information_schema.tables where table_catalog = ? and table_schema = ? and table_name = ? and table_type = 'BASE TABLE'", ['laravel', 'public', 'foo'])->andReturn(['countable_result']);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['schema' => 'public', 'name' => 'foo']]);
        $connection->shouldReceive('getTablePrefix');
        $connection->shouldReceive('getConfig')->with('database')->andReturn('laravel');
        $builder = $this->getBuilder($connection);
        $processor->shouldReceive('processTables')->andReturn([['schema' => 'public', 'name' => 'foo']]);

        $builder->hasTable('foo');
        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('public.foo'));
    }

    public function test_has_table_when_schema_unqualified_and_search_path_filled()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('myapp,public');
        $grammar = m::mock(CockroachDbGrammar::class);
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['schema' => 'myapp', 'name' => 'foo']]);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);
        $processor->shouldReceive('processTables')->andReturn([['schema' => 'myapp', 'name' => 'foo']]);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function test_has_table_when_schema_unqualified_and_search_path_fallback_filled()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(['myapp', 'public']);
        $grammar = m::mock(CockroachDbGrammar::class);
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['schema' => 'myapp', 'name' => 'foo']]);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);
        $processor->shouldReceive('processTables')->andReturn([['schema' => 'myapp', 'name' => 'foo']]);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function test_has_table_when_schema_unqualified_and_search_path_is_user_variable()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('$user');
        $grammar = m::mock(CockroachDbGrammar::class);
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['schema' => 'foouser', 'name' => 'foo']]);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);
        $processor->shouldReceive('processTables')->andReturn([['schema' => 'foouser', 'name' => 'foo']]);

        $this->assertTrue($builder->hasTable('foo'));
        $this->assertTrue($builder->hasTable('foouser.foo'));
    }

    public function test_has_table_when_schema_qualified_and_search_path_mismatches()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(CockroachDbGrammar::class);
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['schema' => 'myapp', 'name' => 'foo']]);
        $connection->shouldReceive('getTablePrefix');
        $builder = $this->getBuilder($connection);
        $processor->shouldReceive('processTables')->andReturn([['schema' => 'myapp', 'name' => 'foo']]);

        $this->assertTrue($builder->hasTable('myapp.foo'));
    }

    public function test_has_table_when_database_and_schema_qualified_and_search_path_mismatches()
    {
        $this->skipIfOlderThan('11.0.0');

        $this->expectException(\InvalidArgumentException::class);

        $connection = $this->getConnection();
        $grammar = m::mock(CockroachDbGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $builder = $this->getBuilder($connection);

        $builder->hasTable('mydatabase.myapp.foo');
    }

    public function test_get_column_listing_when_schema_unqualified_and_search_path_missing()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn(null);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(null);
        $grammar = m::mock(CockroachDbGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('public', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'some_column']]);
        $connection->shouldReceive('getTablePrefix');
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function test_get_column_listing_when_schema_unqualified_and_search_path_filled()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('myapp,public');
        $grammar = m::mock(CockroachDbGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('myapp', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'some_column']]);
        $connection->shouldReceive('getTablePrefix');
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function test_get_column_listing_when_schema_unqualified_and_search_path_is_user_variable()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('$user');
        $grammar = m::mock(CockroachDbGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('foouser', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'some_column']]);
        $connection->shouldReceive('getTablePrefix');
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('foo');
    }

    public function test_get_column_listing_when_schema_qualified_and_search_path_mismatches()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(CockroachDbGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $grammar->shouldReceive('compileColumns')->with('myapp', 'foo')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'some_column']]);
        $connection->shouldReceive('getTablePrefix');
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processColumns')->andReturn([['name' => 'some_column']]);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('myapp.foo');
    }

    public function test_get_column_when_database_and_schema_qualified_and_search_path_mismatches()
    {
        $this->skipIfOlderThan('11.0.0');

        $this->expectException(\InvalidArgumentException::class);

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $grammar = m::mock(CockroachDbGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $builder = $this->getBuilder($connection);

        $builder->getColumnListing('mydatabase.myapp.foo');
    }

    public function test_drop_all_tables_when_search_path_is_string()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('public');
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(CockroachDbGrammar::class);
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $processor->shouldReceive('processTables')->once()->andReturn([['name' => 'users', 'schema' => 'public']]);
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'users', 'schema' => 'public']]);
        $grammar->shouldReceive('escapeNames')->with(['public'])->andReturn(['"public"']);
        $grammar->shouldReceive('escapeNames')->with(['foo'])->andReturn(['"foo"']);
        $grammar->shouldReceive('escapeNames')->with(['users', 'public.users'])->andReturn(['"users"', '"public"."users"']);
        $grammar->shouldReceive('compileDropAllTables')->with(['public.users'])->andReturn('drop table "public"."users" cascade');
        $connection->shouldReceive('statement')->with('drop table "public"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function test_drop_all_tables_when_search_path_is_string_of_many()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn('"$user", public, foo_bar-Baz.Áüõß');
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(CockroachDbGrammar::class);
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processTables')->once()->andReturn([['name' => 'users', 'schema' => 'foouser']]);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'users', 'schema' => 'foouser']]);
        $grammar->shouldReceive('escapeNames')->with(['foouser', 'public', 'foo_bar-Baz.Áüõß'])->andReturn(['"foouser"', '"public"', '"foo_bar-Baz"."Áüõß"']);
        $grammar->shouldReceive('escapeNames')->with(['foo'])->andReturn(['"foo"']);
        $grammar->shouldReceive('escapeNames')->with(['foouser'])->andReturn(['"foouser"']);
        $grammar->shouldReceive('escapeNames')->with(['users', 'foouser.users'])->andReturn(['"users"', '"foouser"."users"']);
        $grammar->shouldReceive('compileDropAllTables')->with(['foouser.users'])->andReturn('drop table "foouser"."users" cascade');
        $connection->shouldReceive('statement')->with('drop table "foouser"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    public function test_drop_all_tables_when_search_path_is_array_of_many()
    {
        $this->skipIfOlderThan('11.0.0');

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->with('username')->andReturn('foouser');
        $connection->shouldReceive('getConfig')->with('search_path')->andReturn([
            '$user',
            '"dev"',
            "'test'",
            'spaced schema',
        ]);
        $connection->shouldReceive('getConfig')->with('dont_drop')->andReturn(['foo']);
        $grammar = m::mock(CockroachDbGrammar::class);
        $processor = m::mock(CockroachDbProcessor::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $processor->shouldReceive('processTables')->once()->andReturn([['name' => 'users', 'schema' => 'foouser']]);
        $grammar->shouldReceive('compileTables')->andReturn('sql');
        $connection->shouldReceive('selectFromWriteConnection')->with('sql')->andReturn([['name' => 'users', 'schema' => 'foouser']]);
        $grammar->shouldReceive('escapeNames')->with(['foouser', 'dev', 'test', 'spaced schema'])->andReturn(['"foouser"', '"dev"', '"test"', '"spaced schema"']);
        $grammar->shouldReceive('escapeNames')->with(['foo'])->andReturn(['"foo"']);
        $grammar->shouldReceive('escapeNames')->with(['users', 'foouser.users'])->andReturn(['"users"', '"foouser"."users"']);
        $grammar->shouldReceive('escapeNames')->with(['foouser'])->andReturn(['"foouser"']);
        $grammar->shouldReceive('compileDropAllTables')->with(['foouser.users'])->andReturn('drop table "foouser"."users" cascade');
        $connection->shouldReceive('statement')->with('drop table "foouser"."users" cascade');
        $builder = $this->getBuilder($connection);

        $builder->dropAllTables();
    }

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }

    protected function getBuilder($connection)
    {
        return new CockroachDbBuilder($connection);
    }

    protected function getGrammar()
    {
        return new CockroachDbGrammar();
    }
}
