<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('basic create and retrieve', function () {
    $post = Post::create(['title' => Str::random(), 'updated_at' => '2016-10-10 10:10:10']);

    expect($post->fresh()->updated_at->toDateString())->toBe('2016-10-10');

    $post->comments()->create(['title' => Str::random()]);

    $this->assertNotSame('2016-10-10', $post->fresh()->updated_at->toDateString());
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->timestamps();
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('post_id');
        $table->string('title');
        $table->timestamps();
    });
}

function comments()
{
    return test()->hasMany(Comment::class, 'post_id');
}

function boot()
{
    parent::boot();

    static::addGlobalScope('age', function ($builder) {
        $builder->join('comments', 'comments.post_id', '=', 'posts.id');
    });
}

function post()
{
    return test()->belongsTo(Post::class, 'post_id');
}
