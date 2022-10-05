<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaBuilderTest extends DatabaseTestCase
{
    protected function destroyDatabaseMigrations()
    {
        Schema::dropAllViews();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function test_drop_all_tables()
    {
        $this->expectNotToPerformAssertions();

        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::dropAllTables();

        $this->artisan('migrate:install');

        Schema::create('table', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    /**
     * @doesNotPerformAssertions
     */
    public function test_drop_all_views()
    {
        $this->expectNotToPerformAssertions();

        DB::statement('create view foo (id) as select 1');

        Schema::dropAllViews();

        DB::statement('create view foo (id) as select 1');
    }
}
