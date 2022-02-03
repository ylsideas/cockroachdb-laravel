<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('parent is not null', function () {
    $child = Comment::first();
    $parent = null;

    expect($child->commentable()->is($parent))->toBeFalse();
    expect($child->commentable()->isNot($parent))->toBeTrue();
});

test('parent is model', function () {
    $child = Comment::first();
    $parent = Post::first();

    expect($child->commentable()->is($parent))->toBeTrue();
    expect($child->commentable()->isNot($parent))->toBeFalse();
});

test('parent is not another model', function () {
    $child = Comment::first();
    $parent = new Post();
    $parent->id = 2;

    expect($child->commentable()->is($parent))->toBeFalse();
    expect($child->commentable()->isNot($parent))->toBeTrue();
});

test('null parent is not model', function () {
    $child = Comment::first();
    $child->commentable()->dissociate();
    $parent = Post::first();

    expect($child->commentable()->is($parent))->toBeFalse();
    expect($child->commentable()->isNot($parent))->toBeTrue();
});

test('parent is not model with another table', function () {
    $child = Comment::first();
    $parent = Post::first();
    $parent->setTable('foo');

    expect($child->commentable()->is($parent))->toBeFalse();
    expect($child->commentable()->isNot($parent))->toBeTrue();
});

test('parent is not model with another connection', function () {
    $child = Comment::first();
    $parent = Post::first();
    $parent->setConnection('foo');

    expect($child->commentable()->is($parent))->toBeFalse();
    expect($child->commentable()->isNot($parent))->toBeTrue();
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
        $table->string('commentable_type');
        $table->integer('commentable_id');
    });

    $post = Post::create();
    (new Comment())->commentable()->associate($post)->save();
}

function commentable()
{
    return test()->morphTo();
}
