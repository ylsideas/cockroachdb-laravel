<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('lazy eager loading', function () {
    $comment = Comment::first();

    $comment->loadMorphCount('commentable', [
        Post::class => ['likes'],
    ]);

    expect($comment->relationLoaded('commentable'))->toBeTrue();
    expect($comment->commentable->likes_count)->toEqual(2);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('likes', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('post_id');
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('commentable_type');
        $table->integer('commentable_id');
    });

    $post = Post::create();

    tap((new Like())->post()->associate($post))->save();
    tap((new Like())->post()->associate($post))->save();

    (new Comment())->commentable()->associate($post)->save();
}

function commentable()
{
    return test()->morphTo();
}

function likes()
{
    return test()->hasMany(Like::class);
}

function post()
{
    return test()->belongsTo(Post::class);
}
