<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

if (PHP_VERSION_ID >= 80100) {
    include_once 'Enums.php';
}

/**
 * @requires PHP >= 8.1
 */
beforeEach(function () {
    if (version_compare(App::version(), '8.69', '<')) {
        $this->markTestSkipped('Not included before 8.69');
    }
});

test('can query with enums', function () {
    DB::table('enum_casts')->insert([
        'string_status' => 'pending',
        'integer_status' => 1,
    ]);

    $record = DB::table('enum_casts')->where('string_status', StringStatus::pending)->first();
    $record2 = DB::table('enum_casts')->where('integer_status', IntegerStatus::pending)->first();
    $record3 = DB::table('enum_casts')->whereIn('integer_status', [IntegerStatus::pending])->first();

    $this->assertNotNull($record);
    $this->assertNotNull($record2);
    $this->assertNotNull($record3);
    expect($record->string_status)->toEqual('pending');
    expect($record2->integer_status)->toEqual(1);
});

test('can insert with enums', function () {
    DB::table('enum_casts')->insert([
        'string_status' => StringStatus::pending,
        'integer_status' => IntegerStatus::pending,
    ]);

    $record = DB::table('enum_casts')->where('string_status', StringStatus::pending)->first();

    $this->assertNotNull($record);
    expect($record->string_status)->toEqual('pending');
    expect($record->integer_status)->toEqual(1);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('enum_casts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('string_status', 100)->nullable();
        $table->integer('integer_status')->nullable();
    });
}
