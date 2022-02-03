<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('load count single relation', function () {
    $model = BaseModel::first();

    DB::enableQueryLog();

    $model->loadCount('related1');

    expect(DB::getQueryLog())->toHaveCount(1);
    expect($model->related1_count)->toEqual(2);
});

test('load count multiple relations', function () {
    $model = BaseModel::first();

    DB::enableQueryLog();

    $model->loadCount(['related1', 'related2']);

    expect(DB::getQueryLog())->toHaveCount(1);
    expect($model->related1_count)->toEqual(2);
    expect($model->related2_count)->toEqual(1);
});

test('load count deleted relations', function () {
    $model = BaseModel::first();

    expect($model->deletedrelated_count)->toBeNull();

    $model->loadCount('deletedrelated');

    expect($model->deletedrelated_count)->toEqual(1);

    DeletedRelated::first()->delete();

    $model = BaseModel::first();

    expect($model->deletedrelated_count)->toBeNull();

    $model->loadCount('deletedrelated');

    expect($model->deletedrelated_count)->toEqual(0);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('base_models', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::create('related1s', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('base_model_id');
    });

    Schema::create('related2s', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('base_model_id');
    });

    Schema::create('deleted_related', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('base_model_id');
        $table->softDeletes();
    });

    BaseModel::create(['id' => 1]);

    Related1::create(['base_model_id' => 1]);
    Related1::create(['base_model_id' => 1]);
    Related2::create(['base_model_id' => 1]);
    DeletedRelated::create(['base_model_id' => 1]);
}

function related1()
{
    return test()->hasMany(Related1::class);
}

function related2()
{
    return test()->hasMany(Related2::class);
}

function deletedrelated()
{
    return test()->hasMany(DeletedRelated::class);
}

function parent()
{
    return test()->belongsTo(BaseModel::class);
}
