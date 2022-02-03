<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('where relation', function () {
    $users = User::whereRelation('posts', 'public', true)->get();

    expect($users->pluck('id')->all())->toEqual([1]);
});

test('or where relation', function () {
    $users = User::whereRelation('posts', 'public', true)->orWhereRelation('posts', 'public', false)->get();

    expect($users->pluck('id')->all())->toEqual([1, 2]);
});

test('nested where relation', function () {
    $texts = User::whereRelation('posts.texts', 'content', 'test')->get();

    expect($texts->pluck('id')->all())->toEqual([1]);
});

test('nested or where relation', function () {
    $texts = User::whereRelation('posts.texts', 'content', 'test')->orWhereRelation('posts.texts', 'content', 'test2')->get();

    expect($texts->pluck('id')->all())->toEqual([1, 2]);
});

test('where morph relation', function () {
    $comments = Comment::whereMorphRelation('commentable', '*', 'public', true)->get();

    expect($comments->pluck('id')->all())->toEqual([1]);
});

test('or where morph relation', function () {
    $comments = Comment::whereMorphRelation('commentable', '*', 'public', true)
        ->orWhereMorphRelation('commentable', '*', 'public', false)
        ->get();

    expect($comments->pluck('id')->all())->toEqual([1, 2]);
});

test('with count', function () {
    $users = User::whereHas('posts', function ($query) {
        $query->where('public', true);
    })->get();

    expect($users->pluck('id')->all())->toEqual([1]);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('user_id');
        $table->boolean('public');
    });

    Schema::create('texts', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('post_id');
        $table->text('content');
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('commentable_type');
        $table->integer('commentable_id');
    });

    $user = User::create(['id' => 1]);
    $post = tap((new Post(['id' => 1, 'public' => true]))->user()->associate($user))->save();
    (new Comment(['id' => 1]))->commentable()->associate($post)->save();
    (new Text(['id' => 1, 'content' => 'test']))->post()->associate($post)->save();

    $user = User::create(['id' => 2]);
    $post = tap((new Post(['id' => 2, 'public' => false]))->user()->associate($user))->save();
    (new Comment(['id' => 2]))->commentable()->associate($post)->save();
    (new Text(['id' => 2, 'content' => 'test2']))->post()->associate($post)->save();
}

function commentable()
{
    return test()->morphTo();
}

function comments()
{
    return test()->morphMany(Comment::class, 'commentable');
}

function texts()
{
    return test()->hasMany(Text::class);
}

function user()
{
    return test()->belongsTo(User::class);
}

function post()
{
    return test()->belongsTo(Post::class);
}

function posts()
{
    return test()->hasMany(Post::class);
}
