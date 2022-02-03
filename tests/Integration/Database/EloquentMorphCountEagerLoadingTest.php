<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('with morph count loading', function () {
    $comments = Comment::query()
        ->with(['commentable' => function (MorphTo $morphTo) {
            $morphTo->morphWithCount([Post::class => ['likes']]);
        }])
        ->get();

    $this->assertTrue($comments[0]->relationLoaded('commentable'));
    $this->assertEquals(2, $comments[0]->commentable->likes_count);
    $this->assertTrue($comments[1]->relationLoaded('commentable'));
    $this->assertNull($comments[1]->commentable->views_count);
});

test('with morph count loading with single relation', function () {
    $comments = Comment::query()
        ->with(['commentable' => function (MorphTo $morphTo) {
            $morphTo->morphWithCount([Post::class => 'likes']);
        }])
        ->get();

    $this->assertTrue($comments[0]->relationLoaded('commentable'));
    $this->assertEquals(2, $comments[0]->commentable->likes_count);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('likes', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('post_id');
    });

    Schema::create('views', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('video_id');
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::create('videos', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('commentable_type');
        $table->integer('commentable_id');
    });

    $post = Post::create();
    $video = Video::create();

    tap((new Like())->post()->associate($post))->save();
    tap((new Like())->post()->associate($post))->save();

    tap((new View())->video()->associate($video))->save();

    (new Comment())->commentable()->associate($post)->save();
    (new Comment())->commentable()->associate($video)->save();
}

function commentable()
{
    return test()->morphTo();
}

function likes()
{
    return test()->hasMany(Like::class);
}

function views()
{
    return test()->hasMany(View::class);
}

function post()
{
    return test()->belongsTo(Post::class);
}

function video()
{
    return test()->belongsTo(Video::class);
}
