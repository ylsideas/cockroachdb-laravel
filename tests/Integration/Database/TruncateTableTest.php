<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateTableTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_truncating_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
    }

    public function testTruncatingTables()
    {
        DB::table('test_truncating_table')->insert([['name' => 'test']]);

        DB::table('test_truncating_table')->truncate();

        $this->assertDatabaseMissing('test_truncating_table', ['name' => 'test']);
    }
}
