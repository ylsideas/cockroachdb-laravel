<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('morph constraints', function () {
    $comments = Comment::query()
        ->with(['commentable' => function (MorphTo $morphTo) {
            $morphTo->constrain([
                Post::class => function ($query) {
                    $query->where('post_visible', true);
                },
                Video::class => function ($query) {
                    $query->where('video_visible', true);
                },
            ]);
        }])
        ->get();

    expect($comments[0]->commentable->post_visible)->toBeTrue();
    expect($comments[1]->commentable)->toBeNull();
    expect($comments[2]->commentable->video_visible)->toBeTrue();
    expect($comments[3]->commentable)->toBeNull();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->boolean('post_visible');
    });

    Schema::create('videos', function (Blueprint $table) {
        $table->increments('id');
        $table->boolean('video_visible');
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('commentable_type');
        $table->integer('commentable_id');
    });

    $post1 = Post::create(['post_visible' => true]);
    (new Comment())->commentable()->associate($post1)->save();

    $post2 = Post::create(['post_visible' => false]);
    (new Comment())->commentable()->associate($post2)->save();

    $video1 = Video::create(['video_visible' => true]);
    (new Comment())->commentable()->associate($video1)->save();

    $video2 = Video::create(['video_visible' => false]);
    (new Comment())->commentable()->associate($video2)->save();
}

function commentable()
{
    return test()->morphTo();
}
