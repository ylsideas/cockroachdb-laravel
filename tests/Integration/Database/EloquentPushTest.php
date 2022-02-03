<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('push method saves the relationships recursively', function () {
    $user = new UserX();
    $user->name = 'Test';
    $user->save();
    $user->posts()->create(['title' => 'Test title']);

    $post = PostX::firstOrFail();
    $post->comments()->create(['comment' => 'Test comment']);

    $user = $user->fresh();
    $user->name = 'Test 1';
    $user->posts[0]->title = 'Test title 1';
    $user->posts[0]->comments[0]->comment = 'Test comment 1';
    $user->push();

    expect(UserX::count())->toBe(1);
    expect(UserX::firstOrFail()->name)->toBe('Test 1');
    expect(PostX::count())->toBe(1);
    expect(PostX::firstOrFail()->title)->toBe('Test title 1');
    expect(CommentX::count())->toBe(1);
    expect(CommentX::firstOrFail()->comment)->toBe('Test comment 1');
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->unsignedInteger('user_id');
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('comment');
        $table->unsignedInteger('post_id');
    });
}

function posts()
{
    return test()->hasMany(PostX::class, 'user_id');
}

function comments()
{
    return test()->hasMany(CommentX::class, 'post_id');
}
