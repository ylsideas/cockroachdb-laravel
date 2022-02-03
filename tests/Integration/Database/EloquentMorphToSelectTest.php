<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('select', function () {
    $comments = CommentMorphTest::with('commentable:id')->get();

    $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
});

test('select raw', function () {
    $comments = CommentMorphTest::with(['commentable' => function ($query) {
        $query->selectRaw('id');
    }])->get();

    $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
});

test('select sub', function () {
    $comments = CommentMorphTest::with(['commentable' => function ($query) {
        $query->selectSub(function ($query) {
            $query->select('id');
        }, 'id');
    }])->get();

    $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
});

test('add select', function () {
    $comments = CommentMorphTest::with(['commentable' => function ($query) {
        $query->addSelect('id');
    }])->get();

    $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
});

test('lazy loading', function () {
    $comment = CommentMorphTest::first();
    $post = $comment->commentable()->select('id')->first();

    $this->assertEquals(['id' => 1], $post->getAttributes());
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

    $post = PostMorphTest::create(['id' => 1]);
    (new CommentMorphTest())->setAttribute('id', 2)->commentable()->associate($post)->save();
}

function commentable()
{
    return test()->morphTo();
}
