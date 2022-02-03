<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

it('basic', function () {
    $one = Model1::create(['id' => 1]);
    $two = $one->twos()->Create(['id' => 2]);
    $two->threes()->Create(['id' => 3]);

    $results = Model1::withCount([
        'twos' => function ($query) {
            $query->where('id', '>=', 1);
        },
    ]);

    $this->assertEquals([
        ['id' => 1, 'twos_count' => 1],
    ], $results->get()->toArray());
});

test('global scopes', function () {
    $one = Model1::create(['id' => 1]);
    $one->fours()->create(['id' => 1]);

    $result = Model1::withCount('fours')->first();
    expect($result->fours_count)->toEqual(0);

    $result = Model1::withCount('allFours')->first();
    expect($result->all_fours_count)->toEqual(1);
});

test('sorting scopes', function () {
    $one = Model1::create(['id' => 1]);
    $one->twos()->create();

    $query = Model1::withCount('twos')->getQuery();

    expect($query->orders)->toBeNull();
    expect($query->getRawBindings()['order'])->toBe([]);
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
        $table->integer('two_id');
    });

    Schema::create('four', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('one_id');
    });
}

function twos()
{
    return test()->hasMany(Model2::class, 'one_id');
}

function fours()
{
    return test()->hasMany(Model4::class, 'one_id');
}

function allFours()
{
    return test()->fours()->withoutGlobalScopes();
}

function threes()
{
    return test()->hasMany(Model3::class, 'two_id');
}

function boot()
{
    parent::boot();

    static::addGlobalScope('app', function ($builder) {
        $builder->where('id', '>', 1);
    });
}
