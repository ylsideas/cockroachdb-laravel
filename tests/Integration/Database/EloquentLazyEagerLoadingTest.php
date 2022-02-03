<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('basic', function () {
    $one = Model1::create();
    $one->twos()->create();
    $one->threes()->create();

    $model = Model1::find($one->id);

    $this->assertTrue($model->relationLoaded('twos'));
    $this->assertFalse($model->relationLoaded('threes'));

    DB::enableQueryLog();

    $model->load('threes');

    $this->assertCount(1, DB::getQueryLog());

    $this->assertTrue($model->relationLoaded('threes'));
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
