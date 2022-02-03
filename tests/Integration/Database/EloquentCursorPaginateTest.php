<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('cursor pagination on top of columns', function () {
    for ($i = 1; $i <= 50; $i++) {
        TestPost::create([
            'title' => 'Title '.$i,
        ]);
    }

    expect(TestPost::cursorPaginate(15, ['id', 'title']))->toHaveCount(15);
});

test('pagination with distinct', function () {
    for ($i = 1; $i <= 3; $i++) {
        TestPost::create(['title' => 'Hello world']);
        TestPost::create(['title' => 'Goodbye world']);
    }

    $query = TestPost::query()->distinct();

    expect($query->get()->count())->toEqual(6);
    expect($query->count())->toEqual(6);
    expect($query->cursorPaginate()->items())->toHaveCount(6);
});

test('pagination with where clause', function () {
    for ($i = 1; $i <= 3; $i++) {
        TestPost::create(['title' => 'Hello world', 'user_id' => null]);
        TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
    }

    $query = TestPost::query()->whereNull('user_id');

    expect($query->get()->count())->toEqual(3);
    expect($query->count())->toEqual(3);
    expect($query->cursorPaginate()->items())->toHaveCount(3);
});

/**/
test('pagination with has clause', function () {
    for ($i = 1; $i <= 3; $i++) {
        TestUser::create(['id' => $i]);
        TestPost::create(['title' => 'Hello world', 'user_id' => null]);
        TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
        TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
    }

    $query = TestUser::query()->has('posts');

    expect($query->get()->count())->toEqual(2);
    expect($query->count())->toEqual(2);
    expect($query->cursorPaginate()->items())->toHaveCount(2);
})->group('SkipMSSQL');

/**/
test('pagination with where has clause', function () {
    for ($i = 1; $i <= 3; $i++) {
        TestUser::create(['id' => $i]);
        TestPost::create(['title' => 'Hello world', 'user_id' => null]);
        TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
        TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
    }

    $query = TestUser::query()->whereHas('posts', function ($query) {
        $query->where('title', 'Howdy');
    });

    expect($query->get()->count())->toEqual(1);
    expect($query->count())->toEqual(1);
    expect($query->cursorPaginate()->items())->toHaveCount(1);
})->group('SkipMSSQL');

/**/
test('pagination with where exists clause', function () {
    for ($i = 1; $i <= 3; $i++) {
        TestUser::create(['id' => $i]);
        TestPost::create(['title' => 'Hello world', 'user_id' => null]);
        TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
        TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
    }

    $query = TestUser::query()->whereExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('test_posts')
            ->whereColumn('test_posts.user_id', 'test_users.id');
    });

    expect($query->get()->count())->toEqual(2);
    expect($query->count())->toEqual(2);
    expect($query->cursorPaginate()->items())->toHaveCount(2);
})->group('SkipMSSQL');

/**/
test('pagination with multiple where clauses', function () {
    for ($i = 1; $i <= 4; $i++) {
        TestUser::create(['id' => $i]);
        TestPost::create(['title' => 'Hello world', 'user_id' => null]);
        TestPost::create(['title' => 'Goodbye world', 'user_id' => 2]);
        TestPost::create(['title' => 'Howdy', 'user_id' => 3]);
        TestPost::create(['title' => 'Howdy', 'user_id' => 4]);
    }

    $query = TestUser::query()->whereExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('test_posts')
            ->whereColumn('test_posts.user_id', 'test_users.id');
    })->whereHas('posts', function ($query) {
        $query->where('title', 'Howdy');
    })->where('id', '<', 5)->orderBy('id');

    $clonedQuery = $query->clone();
    $anotherQuery = $query->clone();

    expect($query->get()->count())->toEqual(2);
    expect($query->count())->toEqual(2);
    expect($query->cursorPaginate()->items())->toHaveCount(2);
    expect($clonedQuery->cursorPaginate(1)->items())->toHaveCount(1);
    $this->assertCount(
        1,
        $anotherQuery->cursorPaginate(5, ['*'], 'cursor', new Cursor(['id' => 3]))
                    ->items()
    );
})->group('SkipMSSQL');

/**/
test('pagination with aliased order by', function () {
    for ($i = 1; $i <= 6; $i++) {
        TestUser::create(['id' => $i]);
    }

    $query = TestUser::query()->select('id as user_id')->orderBy('user_id');
    $clonedQuery = $query->clone();
    $anotherQuery = $query->clone();

    expect($query->get()->count())->toEqual(6);
    expect($query->count())->toEqual(6);
    expect($query->cursorPaginate()->items())->toHaveCount(6);
    expect($clonedQuery->cursorPaginate(3)->items())->toHaveCount(3);
    $this->assertCount(
        4,
        $anotherQuery->cursorPaginate(10, ['*'], 'cursor', new Cursor(['user_id' => 2]))
                    ->items()
    );
})->group('SkipMSSQL');

test('pagination with distinct columns and select', function () {
    for ($i = 1; $i <= 3; $i++) {
        TestPost::create(['title' => 'Hello world']);
        TestPost::create(['title' => 'Goodbye world']);
    }

    $query = TestPost::query()->orderBy('title')->distinct('title')->select('title');

    expect($query->get()->count())->toEqual(2);
    expect($query->count())->toEqual(2);
    expect($query->cursorPaginate()->items())->toHaveCount(2);
});

test('pagination with distinct columns and select and join', function () {
    for ($i = 1; $i <= 5; $i++) {
        $user = TestUser::create();
        for ($j = 1; $j <= 10; $j++) {
            TestPost::create([
                'title' => 'Title '.$i,
                'user_id' => $user->id,
            ]);
        }
    }

    $query = TestUser::query()->join('test_posts', 'test_posts.user_id', '=', 'test_users.id')
        ->distinct('test_users.id')->select('test_users.*');

    expect($query->get()->count())->toEqual(5);
    expect($query->count())->toEqual(5);
    expect($query->cursorPaginate()->items())->toHaveCount(5);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('test_posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->timestamps();
    });

    Schema::create('test_users', function ($table) {
        $table->increments('id');
        $table->timestamps();
    });
}

function posts()
{
    return test()->hasMany(TestPost::class, 'user_id');
}
