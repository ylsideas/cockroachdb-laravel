<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('has self', function () {
    $users = User::has('parent')->get();

    $this->assertCount(1, $users);
});

test('has self custom owner key', function () {
    $users = User::has('parentBySlug')->get();

    $this->assertCount(1, $users);
});

test('associate with model', function () {
    $parent = User::doesntHave('parent')->first();
    $child = User::has('parent')->first();

    $parent->parent()->associate($child);

    $this->assertEquals($child->id, $parent->parent_id);
    $this->assertEquals($child->id, $parent->parent->id);
});

test('associate with id', function () {
    $parent = User::doesntHave('parent')->first();
    $child = User::has('parent')->first();

    $parent->parent()->associate($child->id);

    $this->assertEquals($child->id, $parent->parent_id);
    $this->assertEquals($child->id, $parent->parent->id);
});

test('associate with id unsets loaded relation', function () {
    $child = User::has('parent')->with('parent')->first();

    // Overwrite the (loaded) parent relation
    $child->parent()->associate($child->id);

    $this->assertEquals($child->id, $child->parent_id);
    $this->assertFalse($child->relationLoaded('parent'));
});

test('parent is not null', function () {
    $child = User::has('parent')->first();
    $parent = null;

    $this->assertFalse($child->parent()->is($parent));
    $this->assertTrue($child->parent()->isNot($parent));
});

test('parent is model', function () {
    $child = User::has('parent')->first();
    $parent = User::doesntHave('parent')->first();

    $this->assertTrue($child->parent()->is($parent));
    $this->assertFalse($child->parent()->isNot($parent));
});

test('parent is not another model', function () {
    $child = User::has('parent')->first();
    $parent = new User();
    $parent->id = 3;

    $this->assertFalse($child->parent()->is($parent));
    $this->assertTrue($child->parent()->isNot($parent));
});

test('null parent is not model', function () {
    $child = User::has('parent')->first();
    $child->parent()->dissociate();
    $parent = User::doesntHave('parent')->first();

    $this->assertFalse($child->parent()->is($parent));
    $this->assertTrue($child->parent()->isNot($parent));
});

test('parent is not model with another table', function () {
    $child = User::has('parent')->first();
    $parent = User::doesntHave('parent')->first();
    $parent->setTable('foo');

    $this->assertFalse($child->parent()->is($parent));
    $this->assertTrue($child->parent()->isNot($parent));
});

test('parent is not model with another connection', function () {
    $child = User::has('parent')->first();
    $parent = User::doesntHave('parent')->first();
    $parent->setConnection('foo');

    $this->assertFalse($child->parent()->is($parent));
    $this->assertTrue($child->parent()->isNot($parent));
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
