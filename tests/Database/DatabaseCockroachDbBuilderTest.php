<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

use Illuminate\Database\Connection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use YlsIdeas\CockroachDb\Builder\CockroachDbBuilder;
use YlsIdeas\CockroachDb\Schema\CockroachGrammar;

class DatabaseCockroachDbBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCreateDatabase()
    {
        $grammar = new CockroachGrammar();

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'create database "my_temporary_database" encoding "utf8"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);
        $builder->createDatabase('my_temporary_database');
    }

    public function testDropDatabaseIfExists()
    {
        $grammar = new CockroachGrammar();

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'drop database if exists "my_database_a"'
        )->andReturn(true);

        $builder = $this->getBuilder($connection);

        $builder->dropDatabaseIfExists('my_database_a');
    }

    protected function getBuilder($connection)
    {
        return new CockroachDbBuilder($connection);
    }
}
