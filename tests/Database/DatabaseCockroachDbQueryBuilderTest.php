<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression as Raw;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use YlsIdeas\CockroachDb\Exceptions\FeatureNotSupportedException;
use YlsIdeas\CockroachDb\Processor\CockroachDbProcessor;
use YlsIdeas\CockroachDb\Query\CockroachGrammar;

class DatabaseCockroachDbQueryBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testWhereTimeOperatorOptional()
    {
        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '22:00');
        $this->assertSame('select * from "users" where "created_at"::time = ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereDate()
    {
        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', '=', '2015-12-21');
        $this->assertSame('select * from "users" where "created_at"::date = ?', $builder->toSql());
        $this->assertEquals([0 => '2015-12-21'], $builder->getBindings());

        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->whereDate('created_at', new Raw('NOW()'));
        $this->assertSame('select * from "users" where "created_at"::date = NOW()', $builder->toSql());
    }

    public function testWhereDay()
    {
        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->whereDay('created_at', '=', 1);
        $this->assertSame('select * from "users" where extract(day from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 1], $builder->getBindings());
    }

    public function testWhereMonth()
    {
        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->whereMonth('created_at', '=', 5);
        $this->assertSame('select * from "users" where extract(month from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 5], $builder->getBindings());
    }

    public function testWhereYear()
    {
        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->whereYear('created_at', '=', 2014);
        $this->assertSame('select * from "users" where extract(year from "created_at") = ?', $builder->toSql());
        $this->assertEquals([0 => 2014], $builder->getBindings());
    }

    public function testWhereTime()
    {
        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->whereTime('created_at', '>=', '22:00');
        $this->assertSame('select * from "users" where "created_at"::time >= ?', $builder->toSql());
        $this->assertEquals([0 => '22:00'], $builder->getBindings());
    }

    public function testWhereLike()
    {
        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->where('id', 'like', '1');
        $this->assertSame('select * from "users" where "id"::text like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->where('id', 'LIKE', '1');
        $this->assertSame('select * from "users" where "id"::text LIKE ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->where('id', 'ilike', '1');
        $this->assertSame('select * from "users" where "id"::text ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->where('id', 'not like', '1');
        $this->assertSame('select * from "users" where "id"::text not like ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());

        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->where('id', 'not ilike', '1');
        $this->assertSame('select * from "users" where "id"::text not ilike ?', $builder->toSql());
        $this->assertEquals([0 => '1'], $builder->getBindings());
    }

    public function testUpdateMethodWithJoins()
    {
        $builder = $this->getCockroachDbBuilder();
        $builder->getConnection()
            ->shouldReceive('update')
            ->once()
            ->with('update "users" set "admin" = ? from "blocklist" where "user"."email" = "blocklist"."email"', [0 => false])
            ->andReturn(1);
        $result = $builder
            ->from('users')
            ->join('blocklist', 'user.email', '=', 'blocklist.email')
            ->update(['admin' => false]);
        $this->assertEquals(1, $result);
    }

    public function testDeletesWithJoinsThrowAnException()
    {
        $this->expectException(FeatureNotSupportedException::class);
        $builder = $this->getCockroachDbBuilder();
        $builder->from('users')->join('blocklist', 'email', '=', 'email')->delete();
        $builder->toSql();
    }

    public function testWhereFullTextThrowsExceptionCockroachDb()
    {
        $this->expectException(FeatureNotSupportedException::class);
        $builder = $this->getCockroachDbBuilder();
        $builder->select('*')->from('users')->whereFullText('description', 'should contain');
        $builder->toSql();
    }

    protected function getConnection()
    {
        $connection = m::mock(ConnectionInterface::class);
        $connection->shouldReceive('getDatabaseName')->andReturn('database');

        return $connection;
    }

    protected function getBuilder()
    {
        $grammar = new Grammar();
        $processor = m::mock(Processor::class);

        return new Builder($this->getConnection(), $grammar, $processor);
    }

    protected function getCockroachDbBuilder()
    {
        $grammar = new CockroachGrammar();
        $processor = m::mock(Processor::class);

        return new Builder($this->getConnection(), $grammar, $processor);
    }

    protected function getCockroachDbBuilderWithProcessor()
    {
        $grammar = new CockroachGrammar();
        $processor = new CockroachDbProcessor();

        return new Builder(m::mock(ConnectionInterface::class), $grammar, $processor);
    }
}
