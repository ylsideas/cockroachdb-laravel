<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('load count', function () {
    $posts = Post::all();

    DB::enableQueryLog();

    $posts->loadCount('comments');

    $this->assertCount(1, DB::getQueryLog());
    $this->assertEquals('2', $posts[0]->comments_count);
    $this->assertEquals('0', $posts[1]->comments_count);
    $this->assertEquals('2', $posts[0]->getOriginal('comments_count'));
});

test('load count with same models', function () {
    $posts = Post::all()->push(Post::first());

    DB::enableQueryLog();

    $posts->loadCount('comments');

    $this->assertCount(1, DB::getQueryLog());
    $this->assertEquals('2', $posts[0]->comments_count);
    $this->assertEquals('0', $posts[1]->comments_count);
    $this->assertEquals('2', $posts[2]->comments_count);
});

test('load count on deleted models', function () {
    $posts = Post::all()->each->delete();

    DB::enableQueryLog();

    $posts->loadCount('comments');

    $this->assertCount(1, DB::getQueryLog());
    $this->assertEquals('2', $posts[0]->comments_count);
    $this->assertEquals('0', $posts[1]->comments_count);
});

test('load count with array of relations', function () {
    $posts = Post::all();

    DB::enableQueryLog();

    $posts->loadCount(['comments', 'likes']);

    $this->assertCount(1, DB::getQueryLog());
    $this->assertEquals('2', $posts[0]->comments_count);
    $this->assertEquals('1', $posts[0]->likes_count);
    $this->assertEquals('0', $posts[1]->comments_count);
    $this->assertEquals('0', $posts[1]->likes_count);
});

test('load count does not override attributes with default value', function () {
    $post = Post::first();
    $post->some_default_value = 200;

    Collection::make([$post])->loadCount('comments');

    $this->assertSame(200, $post->some_default_value);
    $this->assertEquals('2', $post->comments_count);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('some_default_value');
        $table->softDeletes();
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('post_id');
    });

    Schema::create('likes', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('post_id');
    });

    $post = Post::create();
    $post->comments()->saveMany([new Comment(), new Comment()]);

    $post->likes()->save(new Like());

    Post::create();
}

function comments()
{
    return test()->hasMany(Comment::class);
}

function likes()
{
    return test()->hasMany(Like::class);
}
