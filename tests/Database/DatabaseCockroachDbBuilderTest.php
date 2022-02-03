<?php

use Illuminate\Database\Connection;
use Mockery as m;

use YlsIdeas\CockroachDb\Builder\CockroachDbBuilder;
use YlsIdeas\CockroachDb\Schema\CockroachGrammar;

afterEach(function () {
    m::close();
});

test('create database', function () {
    $grammar = new CockroachGrammar();

    $connection = m::mock(Connection::class);
    $connection->shouldReceive('getConfig')->once()->with('charset')->andReturn('utf8');
    $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
    $connection->shouldReceive('statement')->once()->with(
        'create database "my_temporary_database" encoding "utf8"'
    )->andReturn(true);

    $builder = getBuilder($connection);
    $builder->createDatabase('my_temporary_database');
});

test('drop database if exists', function () {
    $grammar = new CockroachGrammar();

    $connection = m::mock(Connection::class);
    $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
    $connection->shouldReceive('statement')->once()->with(
        'drop database if exists "my_database_a"'
    )->andReturn(true);

    $builder = getBuilder($connection);

    $builder->dropDatabaseIfExists('my_database_a');
});

// Helpers
function getBuilder($connection)
{
    return new CockroachDbBuilder($connection);
}
