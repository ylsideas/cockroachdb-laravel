<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

if (PHP_VERSION_ID >= 80100) {
    include 'Enums.php';
}

/**
 * @requires PHP 8.1
 */
beforeEach(function () {
    if (version_compare(App::version(), '8.69', '<')) {
        $this->markTestSkipped('Not included before 8.69');
    }
});

test('enums are castable', function () {
    DB::table('enum_casts')->insert([
        'string_status' => 'pending',
        'integer_status' => 1,
    ]);

    $model = EloquentModelEnumCastingTestModel::first();

    $this->assertEquals(StringStatus::pending, $model->string_status);
    $this->assertEquals(IntegerStatus::pending, $model->integer_status);
});

test('enums return null when null', function () {
    DB::table('enum_casts')->insert([
        'string_status' => null,
        'integer_status' => null,
    ]);

    $model = EloquentModelEnumCastingTestModel::first();

    $this->assertEquals(null, $model->string_status);
    $this->assertEquals(null, $model->integer_status);
});

test('enums are castable to array', function () {
    $model = new EloquentModelEnumCastingTestModel([
        'string_status' => StringStatus::pending,
        'integer_status' => IntegerStatus::pending,
    ]);

    $this->assertEquals([
        'string_status' => 'pending',
        'integer_status' => 1,
    ], $model->toArray());
});

test('enums are castable to array when null', function () {
    $model = new EloquentModelEnumCastingTestModel([
        'string_status' => null,
        'integer_status' => null,
    ]);

    $this->assertEquals([
        'string_status' => null,
        'integer_status' => null,
    ], $model->toArray());
});

test('enums are converted on save', function () {
    $model = new EloquentModelEnumCastingTestModel([
        'string_status' => StringStatus::pending,
        'integer_status' => IntegerStatus::pending,
    ]);

    $model->save();

    $this->assertEquals((object) [
        'id' => $model->id,
        'string_status' => 'pending',
        'integer_status' => 1,
    ], DB::table('enum_casts')->where('id', $model->id)->first());
});

test('enums accept null on save', function () {
    $model = new EloquentModelEnumCastingTestModel([
        'string_status' => null,
        'integer_status' => null,
    ]);

    $model->save();

    $this->assertEquals((object) [
        'id' => $model->id,
        'string_status' => null,
        'integer_status' => null,
    ], DB::table('enum_casts')->where('id', $model->id)->first());
});

test('enums accept backed value on save', function () {
    $model = new EloquentModelEnumCastingTestModel([
        'string_status' => 'pending',
        'integer_status' => 1,
    ]);

    $model->save();

    $model = EloquentModelEnumCastingTestModel::first();

    $this->assertEquals(StringStatus::pending, $model->string_status);
    $this->assertEquals(IntegerStatus::pending, $model->integer_status);
});

test('first or new', function () {
    DB::table('enum_casts')->insert([
        'string_status' => 'pending',
        'integer_status' => 1,
    ]);

    $model = EloquentModelEnumCastingTestModel::firstOrNew([
        'string_status' => StringStatus::pending,
    ]);

    $model2 = EloquentModelEnumCastingTestModel::firstOrNew([
        'string_status' => StringStatus::done,
    ]);

    $this->assertTrue($model->exists);
    $this->assertFalse($model2->exists);

    $model2->save();

    $this->assertEquals(StringStatus::done, $model2->string_status);
});

test('first or create', function () {
    DB::table('enum_casts')->insert([
        'string_status' => 'pending',
        'integer_status' => 1,
    ]);

    $model = EloquentModelEnumCastingTestModel::firstOrCreate([
        'string_status' => StringStatus::pending,
    ]);

    $model2 = EloquentModelEnumCastingTestModel::firstOrCreate([
        'string_status' => StringStatus::done,
    ]);

    $this->assertEquals(StringStatus::pending, $model->string_status);
    $this->assertEquals(StringStatus::done, $model2->string_status);
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
