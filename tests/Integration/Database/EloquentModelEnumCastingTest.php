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

    expect($model->string_status)->toEqual(StringStatus::pending);
    expect($model->integer_status)->toEqual(IntegerStatus::pending);
});

test('enums return null when null', function () {
    DB::table('enum_casts')->insert([
        'string_status' => null,
        'integer_status' => null,
    ]);

    $model = EloquentModelEnumCastingTestModel::first();

    expect($model->string_status)->toEqual(null);
    expect($model->integer_status)->toEqual(null);
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

    expect($model->string_status)->toEqual(StringStatus::pending);
    expect($model->integer_status)->toEqual(IntegerStatus::pending);
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

    expect($model->exists)->toBeTrue();
    expect($model2->exists)->toBeFalse();

    $model2->save();

    expect($model2->string_status)->toEqual(StringStatus::done);
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

    expect($model->string_status)->toEqual(StringStatus::pending);
    expect($model2->string_status)->toEqual(StringStatus::done);
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
