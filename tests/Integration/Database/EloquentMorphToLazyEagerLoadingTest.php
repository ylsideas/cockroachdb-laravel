<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('lazy eager loading', function () {
    $comments = Comment::all();

    DB::enableQueryLog();

    $comments->load('commentable');

    expect(DB::getQueryLog())->toHaveCount(3);
    expect($comments[0]->relationLoaded('commentable'))->toBeTrue();
    expect($comments[0]->commentable->relationLoaded('user'))->toBeTrue();
    expect($comments[1]->relationLoaded('commentable'))->toBeTrue();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->increments('post_id');
        $table->unsignedInteger('user_id');
    });

    Schema::create('videos', function (Blueprint $table) {
        $table->increments('video_id');
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('commentable_type');
        $table->integer('commentable_id');
    });

    $user = User::create();

    $post = tap((new Post())->user()->associate($user))->save();

    $video = Video::create();

    (new Comment())->commentable()->associate($post)->save();
    (new Comment())->commentable()->associate($video)->save();
}

function commentable()
{
    return test()->morphTo();
}

function user()
{
    return test()->belongsTo(User::class);
}
