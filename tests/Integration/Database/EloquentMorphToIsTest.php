<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('parent is not null', function () {
    $child = Comment::first();
    $parent = null;

    $this->assertFalse($child->commentable()->is($parent));
    $this->assertTrue($child->commentable()->isNot($parent));
});

test('parent is model', function () {
    $child = Comment::first();
    $parent = Post::first();

    $this->assertTrue($child->commentable()->is($parent));
    $this->assertFalse($child->commentable()->isNot($parent));
});

test('parent is not another model', function () {
    $child = Comment::first();
    $parent = new Post();
    $parent->id = 2;

    $this->assertFalse($child->commentable()->is($parent));
    $this->assertTrue($child->commentable()->isNot($parent));
});

test('null parent is not model', function () {
    $child = Comment::first();
    $child->commentable()->dissociate();
    $parent = Post::first();

    $this->assertFalse($child->commentable()->is($parent));
    $this->assertTrue($child->commentable()->isNot($parent));
});

test('parent is not model with another table', function () {
    $child = Comment::first();
    $parent = Post::first();
    $parent->setTable('foo');

    $this->assertFalse($child->commentable()->is($parent));
    $this->assertTrue($child->commentable()->isNot($parent));
});

test('parent is not model with another connection', function () {
    $child = Comment::first();
    $parent = Post::first();
    $parent->setConnection('foo');

    $this->assertFalse($child->commentable()->is($parent));
    $this->assertTrue($child->commentable()->isNot($parent));
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
