<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('drop all tables', function () {
    $this->expectNotToPerformAssertions();

    Schema::create('table', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::dropAllTables();

    $this->artisan('migrate:install');

    Schema::create('table', function (Blueprint $table) {
        $table->increments('id');
    });
});

test('drop all views', function () {
    $this->expectNotToPerformAssertions();

    DB::statement('create view foo (id) as select 1');

    Schema::dropAllViews();

    DB::statement('create view foo (id) as select 1');
});

// Helpers
function destroyDatabaseMigrations()
{
    Schema::dropAllViews();
}
