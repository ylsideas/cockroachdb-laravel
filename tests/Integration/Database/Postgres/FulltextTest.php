<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database\Postgres;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Exceptions\FeatureNotSupportedException;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;
use YlsIdeas\CockroachDb\Tests\WithMultipleApplicationVersions;

/**
 * @requires extension pdo_pgsql
 * @requires OS Linux|Darwin
 */
class FulltextTest extends DatabaseTestCase
{
    use WithMultipleApplicationVersions;

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id('id');
            $table->string('title', 200);
            $table->text('body');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('articles');
    }

    public function testWhereFulltext()
    {
        $this->skipIfOlderThan('8.79');

        DB::table('articles')->insert([
            ['title' => 'PostgreSQL Tutorial', 'body' => 'DBMS stands for DataBase ...'],
            ['title' => 'How To Use PostgreSQL Well', 'body' => 'After you went through a ...'],
            ['title' => 'Optimizing PostgreSQL', 'body' => 'In this tutorial, we show ...'],
            ['title' => '1001 PostgreSQL Tricks', 'body' => '1. Never run mysqld as root. 2. ...'],
            ['title' => 'PostgreSQL vs. YourSQL', 'body' => 'In the following database comparison ...'],
            ['title' => 'PostgreSQL Security', 'body' => 'When configured properly, PostgreSQL ...'],
        ]);

        $this->expectException(FeatureNotSupportedException::class);
        DB::table('articles')
            ->whereFulltext(['title', 'body'], 'database')
            ->orderBy('id')
            ->get();
    }
}
