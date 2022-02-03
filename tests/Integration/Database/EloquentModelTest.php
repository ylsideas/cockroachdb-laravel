<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(DatabaseTestCase::class);

test('user can update nullable date', function () {
    $user = TestModel1::create([
        'nullable_date' => null,
    ]);

    $user->fill([
        'nullable_date' => $now = Carbon::now(),
    ]);
    expect($user->isDirty('nullable_date'))->toBeTrue();

    $user->save();
    expect($user->nullable_date->toDateString())->toEqual($now->toDateString());
});

test('attribute changes', function () {
    $user = TestModel2::create([
        'name' => Str::random(), 'title' => Str::random(),
    ]);

    expect($user->getDirty())->toBeEmpty();
    expect($user->getChanges())->toBeEmpty();
    expect($user->isDirty())->toBeFalse();
    expect($user->wasChanged())->toBeFalse();

    $user->name = $name = Str::random();

    expect($user->getDirty())->toEqual(['name' => $name]);
    expect($user->getChanges())->toBeEmpty();
    expect($user->isDirty())->toBeTrue();
    expect($user->wasChanged())->toBeFalse();

    $user->save();

    expect($user->getDirty())->toBeEmpty();
    expect($user->getChanges())->toEqual(['name' => $name]);
    expect($user->wasChanged())->toBeTrue();
    expect($user->wasChanged('name'))->toBeTrue();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('test_model1', function (Blueprint $table) {
        $table->increments('id');
        $table->timestamp('nullable_date')->nullable();
    });

    Schema::create('test_model2', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('title');
    });
}
