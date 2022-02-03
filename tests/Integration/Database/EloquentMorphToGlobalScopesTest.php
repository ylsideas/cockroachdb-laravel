<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('with global scopes', function () {
    $comments = Comment::with('commentable')->get();

    $this->assertNotNull($comments[0]->commentable);
    expect($comments[1]->commentable)->toBeNull();
});

test('without global scope', function () {
    $comments = Comment::with(['commentable' => function ($query) {
        $query->withoutGlobalScopes([SoftDeletingScope::class]);
    }])->get();

    $this->assertNotNull($comments[0]->commentable);
    $this->assertNotNull($comments[1]->commentable);
});

test('without global scopes', function () {
    $comments = Comment::with(['commentable' => function ($query) {
        $query->withoutGlobalScopes();
    }])->get();

    $this->assertNotNull($comments[0]->commentable);
    $this->assertNotNull($comments[1]->commentable);
});

test('lazy loading', function () {
    $comment = Comment::latest('id')->first();
    $post = $comment->commentable()->withoutGlobalScopes()->first();

    $this->assertNotNull($post);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->softDeletes();
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('commentable_type');
        $table->integer('commentable_id');
    });

    $post = Post::create();
    (new Comment())->commentable()->associate($post)->save();

    $post = tap(Post::create())->delete();
    (new Comment())->commentable()->associate($post)->save();
}

function commentable()
{
    return test()->morphTo();
}
