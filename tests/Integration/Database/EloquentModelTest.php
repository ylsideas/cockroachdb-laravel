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
    $this->assertTrue($user->isDirty('nullable_date'));

    $user->save();
    $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
});

test('attribute changes', function () {
    $user = TestModel2::create([
        'name' => Str::random(), 'title' => Str::random(),
    ]);

    $this->assertEmpty($user->getDirty());
    $this->assertEmpty($user->getChanges());
    $this->assertFalse($user->isDirty());
    $this->assertFalse($user->wasChanged());

    $user->name = $name = Str::random();

    $this->assertEquals(['name' => $name], $user->getDirty());
    $this->assertEmpty($user->getChanges());
    $this->assertTrue($user->isDirty());
    $this->assertFalse($user->wasChanged());

    $user->save();

    $this->assertEmpty($user->getDirty());
    $this->assertEquals(['name' => $name], $user->getChanges());
    $this->assertTrue($user->wasChanged());
    $this->assertTrue($user->wasChanged('name'));
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
