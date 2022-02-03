<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(DatabaseTestCase::class);

test('basic create and retrieve', function () {
    $user = User::create(['id' => 1, 'name' => Str::random()]);

    $team1 = Team::create(['id' => 1, 'owner_id' => $user->id]);
    $team2 = Team::create(['id' => 2, 'owner_id' => $user->id]);

    $mate1 = User::create(['id' => 2, 'name' => 'John', 'team_id' => $team1->id]);
    $mate2 = User::create(['id' => 3, 'name' => 'Jack', 'team_id' => $team2->id, 'slug' => null]);

    User::create(['name' => Str::random()]);

    $this->assertEquals([$mate1->id, $mate2->id], $user->teamMates->pluck('id')->toArray());
    $this->assertEquals([$user->id], User::has('teamMates')->pluck('id')->toArray());

    $result = $user->teamMates()->first();
    $this->assertEquals(
        $mate1->refresh()->getAttributes() + ['laravel_through_key' => '1'],
        $result->getAttributes()
    );

    $result = $user->teamMates()->firstWhere('name', 'Jack');
    $this->assertEquals(
        $mate2->refresh()->getAttributes() + ['laravel_through_key' => '1'],
        $result->getAttributes()
    );
});

test('global scope columns', function () {
    $user = User::create(['id' => 1, 'name' => Str::random()]);

    $team1 = Team::create(['id' => 1, 'owner_id' => $user->id]);

    User::create(['id' => 2, 'name' => Str::random(), 'team_id' => $team1->id]);

    $teamMates = $user->teamMatesWithGlobalScope;

    $this->assertEquals(['id' => 2, 'laravel_through_key' => 1], $teamMates[0]->getAttributes());
});

test('has self', function () {
    $user = User::create(['name' => Str::random()]);

    $team = Team::create(['owner_id' => $user->id]);

    User::create(['name' => Str::random(), 'team_id' => $team->id]);

    $users = User::has('teamMates')->get();

    $this->assertCount(1, $users);
});

test('has self custom owner key', function () {
    $user = User::create(['slug' => Str::random(), 'name' => Str::random()]);

    $team = Team::create(['owner_slug' => $user->slug]);

    User::create(['name' => Str::random(), 'team_id' => $team->id]);

    $users = User::has('teamMatesBySlug')->get();

    $this->assertCount(1, $users);
});

test('has same parent and through parent table', function () {
    Category::create(['id' => 1]);
    Category::create(['id' => 2]);
    Category::create(['id' => 3, 'parent_id' => 1]);
    Category::create(['id' => 4, 'parent_id' => 2])->delete();

    Product::create(['id' => 1, 'category_id' => 3]);
    Product::create(['id' => 2, 'category_id' => 4]);

    $categories = Category::has('subProducts')->get();

    $this->assertEquals([1], $categories->pluck('id')->all());
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('slug')->nullable();
        $table->integer('team_id')->nullable();
        $table->string('name');
    });

    Schema::create('teams', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('owner_id')->nullable();
        $table->string('owner_slug')->nullable();
    });

    Schema::create('categories', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('parent_id')->nullable();
        $table->softDeletes();
    });

    Schema::create('products', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('category_id');
    });
}

function teamMates()
{
    return test()->hasManyThrough(self::class, Team::class, 'owner_id', 'team_id');
}

function teamMatesBySlug()
{
    return test()->hasManyThrough(self::class, Team::class, 'owner_slug', 'team_id', 'slug');
}

function teamMatesWithGlobalScope()
{
    return test()->hasManyThrough(UserWithGlobalScope::class, Team::class, 'owner_id', 'team_id');
}

function boot()
{
    parent::boot();

    static::addGlobalScope(function ($query) {
        $query->select('users.id');
    });
}

function subProducts()
{
    return test()->hasManyThrough(Product::class, self::class, 'parent_id');
}
