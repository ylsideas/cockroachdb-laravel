<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('pagination on top of columns', function () {
    for ($i = 1; $i <= 50; $i++) {
        PostPagination::create([
            'title' => 'Title '.$i,
        ]);
    }

    expect(PostPagination::paginate(15, ['id', 'title']))->toHaveCount(15);
});

test('pagination with distinct', function () {
    for ($i = 1; $i <= 3; $i++) {
        PostPagination::create(['title' => 'Hello world']);
        PostPagination::create(['title' => 'Goodbye world']);
    }

    $query = PostPagination::query()->distinct();

    expect($query->get()->count())->toEqual(6);
    expect($query->count())->toEqual(6);
    expect($query->paginate()->total())->toEqual(6);
});

test('pagination with distinct and select', function () {
    // This is the 'broken' behaviour, but this test is added to show backwards compatibility.
    for ($i = 1; $i <= 3; $i++) {
        PostPagination::create(['title' => 'Hello world']);
        PostPagination::create(['title' => 'Goodbye world']);
    }

    $query = PostPagination::query()->distinct()->select('title');

    expect($query->get()->count())->toEqual(2);
    expect($query->count())->toEqual(6);
    expect($query->paginate()->total())->toEqual(6);
});

test('pagination with distinct columns and select', function () {
    for ($i = 1; $i <= 3; $i++) {
        PostPagination::create(['title' => 'Hello world']);
        PostPagination::create(['title' => 'Goodbye world']);
    }

    $query = PostPagination::query()->distinct('title')->select('title');

    expect($query->get()->count())->toEqual(2);
    expect($query->count())->toEqual(2);
    expect($query->paginate()->total())->toEqual(2);
});

test('pagination with distinct columns and select and join', function () {
    for ($i = 1; $i <= 5; $i++) {
        $user = UserPagination::create();
        for ($j = 1; $j <= 10; $j++) {
            PostPagination::create([
                'title' => 'Title '.$i,
                'user_id' => $user->id,
            ]);
        }
    }

    $query = UserPagination::query()->join('posts', 'posts.user_id', '=', 'users.id')
        ->distinct('users.id')->select('users.*');

    expect($query->get()->count())->toEqual(5);
    expect($query->count())->toEqual(5);
    expect($query->paginate()->total())->toEqual(5);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title')->nullable();
        $table->unsignedInteger('user_id')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function ($table) {
        $table->increments('id');
        $table->timestamps();
    });
}
