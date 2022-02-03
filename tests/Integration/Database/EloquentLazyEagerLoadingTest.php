<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('basic', function () {
    $one = Model1::create();
    $one->twos()->create();
    $one->threes()->create();

    $model = Model1::find($one->id);

    expect($model->relationLoaded('twos'))->toBeTrue();
    expect($model->relationLoaded('threes'))->toBeFalse();

    DB::enableQueryLog();

    $model->load('threes');

    expect(DB::getQueryLog())->toHaveCount(1);

    expect($model->relationLoaded('threes'))->toBeTrue();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('one', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::create('two', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('one_id');
    });

    Schema::create('three', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('one_id');
    });
}

function twos()
{
    return test()->hasMany(Model2::class, 'one_id');
}

function threes()
{
    return test()->hasMany(Model3::class, 'one_id');
}

function one()
{
    return test()->belongsTo(Model1::class, 'one_id');
}
