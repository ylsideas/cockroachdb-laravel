<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('pagination on top of columns', function () {
    for ($i = 1; $i <= 50; $i++) {
        PostPagination::create([
            'title' => 'Title '.$i,
        ]);
    }

    $this->assertCount(15, PostPagination::paginate(15, ['id', 'title']));
});

test('pagination with distinct', function () {
    for ($i = 1; $i <= 3; $i++) {
        PostPagination::create(['title' => 'Hello world']);
        PostPagination::create(['title' => 'Goodbye world']);
    }

    $query = PostPagination::query()->distinct();

    $this->assertEquals(6, $query->get()->count());
    $this->assertEquals(6, $query->count());
    $this->assertEquals(6, $query->paginate()->total());
});

test('pagination with distinct and select', function () {
    // This is the 'broken' behaviour, but this test is added to show backwards compatibility.
    for ($i = 1; $i <= 3; $i++) {
        PostPagination::create(['title' => 'Hello world']);
        PostPagination::create(['title' => 'Goodbye world']);
    }

    $query = PostPagination::query()->distinct()->select('title');

    $this->assertEquals(2, $query->get()->count());
    $this->assertEquals(6, $query->count());
    $this->assertEquals(6, $query->paginate()->total());
});

test('pagination with distinct columns and select', function () {
    for ($i = 1; $i <= 3; $i++) {
        PostPagination::create(['title' => 'Hello world']);
        PostPagination::create(['title' => 'Goodbye world']);
    }

    $query = PostPagination::query()->distinct('title')->select('title');

    $this->assertEquals(2, $query->get()->count());
    $this->assertEquals(2, $query->count());
    $this->assertEquals(2, $query->paginate()->total());
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

    $this->assertEquals(5, $query->get()->count());
    $this->assertEquals(5, $query->count());
    $this->assertEquals(5, $query->paginate()->total());
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
