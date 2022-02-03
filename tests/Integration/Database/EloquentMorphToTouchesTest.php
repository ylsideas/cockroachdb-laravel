<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('not null', function () {
    $comment = (new Comment())->commentable()->associate(Post::first());

    DB::enableQueryLog();

    $comment->save();

    expect(DB::getQueryLog())->toHaveCount(2);
});

test('null', function () {
    DB::enableQueryLog();

    Comment::create();

    expect(DB::getQueryLog())->toHaveCount(1);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->timestamps();
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->nullableMorphs('commentable');
    });

    Post::create();
}

function commentable()
{
    return test()->morphTo(null, null, null, 'id');
}
