<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('lazy eager loading', function () {
    $comment = Comment::first();

    $comment->loadMorph('commentable', [
        Post::class => ['user'],
    ]);

    $this->assertTrue($comment->relationLoaded('commentable'));
    $this->assertTrue($comment->commentable->relationLoaded('user'));
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

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('commentable_type');
        $table->integer('commentable_id');
    });

    $user = User::create();

    $post = tap((new Post())->user()->associate($user))->save();

    (new Comment())->commentable()->associate($post)->save();
}

function commentable()
{
    return test()->morphTo();
}

function user()
{
    return test()->belongsTo(User::class);
}
