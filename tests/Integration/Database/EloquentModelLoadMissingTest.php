<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('load missing', function () {
    $post = Post::with('comments')->first();

    DB::enableQueryLog();

    $post->loadMissing('comments.parent');

    expect(DB::getQueryLog())->toHaveCount(1);
    expect($post->comments[0]->relationLoaded('parent'))->toBeTrue();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('parent_id')->nullable();
        $table->unsignedInteger('post_id');
    });

    Post::create(['id' => 1]);

    Comment::create(['parent_id' => null, 'post_id' => 1]);
    Comment::create(['parent_id' => 1, 'post_id' => 1]);
}

function parent()
{
    return test()->belongsTo(self::class);
}

function comments()
{
    return test()->hasMany(Comment::class);
}
