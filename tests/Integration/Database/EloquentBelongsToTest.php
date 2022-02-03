<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('has self', function () {
    $users = User::has('parent')->get();

    expect($users)->toHaveCount(1);
});

test('has self custom owner key', function () {
    $users = User::has('parentBySlug')->get();

    expect($users)->toHaveCount(1);
});

test('associate with model', function () {
    $parent = User::doesntHave('parent')->first();
    $child = User::has('parent')->first();

    $parent->parent()->associate($child);

    expect($parent->parent_id)->toEqual($child->id);
    expect($parent->parent->id)->toEqual($child->id);
});

test('associate with id', function () {
    $parent = User::doesntHave('parent')->first();
    $child = User::has('parent')->first();

    $parent->parent()->associate($child->id);

    expect($parent->parent_id)->toEqual($child->id);
    expect($parent->parent->id)->toEqual($child->id);
});

test('associate with id unsets loaded relation', function () {
    $child = User::has('parent')->with('parent')->first();

    // Overwrite the (loaded) parent relation
    $child->parent()->associate($child->id);

    expect($child->parent_id)->toEqual($child->id);
    expect($child->relationLoaded('parent'))->toBeFalse();
});

test('parent is not null', function () {
    $child = User::has('parent')->first();
    $parent = null;

    expect($child->parent()->is($parent))->toBeFalse();
    expect($child->parent()->isNot($parent))->toBeTrue();
});

test('parent is model', function () {
    $child = User::has('parent')->first();
    $parent = User::doesntHave('parent')->first();

    expect($child->parent()->is($parent))->toBeTrue();
    expect($child->parent()->isNot($parent))->toBeFalse();
});

test('parent is not another model', function () {
    $child = User::has('parent')->first();
    $parent = new User();
    $parent->id = 3;

    expect($child->parent()->is($parent))->toBeFalse();
    expect($child->parent()->isNot($parent))->toBeTrue();
});

test('null parent is not model', function () {
    $child = User::has('parent')->first();
    $child->parent()->dissociate();
    $parent = User::doesntHave('parent')->first();

    expect($child->parent()->is($parent))->toBeFalse();
    expect($child->parent()->isNot($parent))->toBeTrue();
});

test('parent is not model with another table', function () {
    $child = User::has('parent')->first();
    $parent = User::doesntHave('parent')->first();
    $parent->setTable('foo');

    expect($child->parent()->is($parent))->toBeFalse();
    expect($child->parent()->isNot($parent))->toBeTrue();
});

test('parent is not model with another connection', function () {
    $child = User::has('parent')->first();
    $parent = User::doesntHave('parent')->first();
    $parent->setConnection('foo');

    expect($child->parent()->is($parent))->toBeFalse();
    expect($child->parent()->isNot($parent))->toBeTrue();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('slug')->nullable();
        $table->unsignedInteger('parent_id')->nullable();
        $table->string('parent_slug')->nullable();
    });

    $user = User::create(['slug' => Str::random()]);
    User::create(['parent_id' => $user->id, 'parent_slug' => $user->slug]);
}

function parent()
{
    return test()->belongsTo(self::class, 'parent_id');
}

function parentBySlug()
{
    return test()->belongsTo(self::class, 'parent_slug', 'slug');
}
