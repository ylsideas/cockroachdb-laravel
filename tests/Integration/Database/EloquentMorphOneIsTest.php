<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('child is not null', function () {
    $parent = Post::first();
    $child = null;

    expect($parent->attachment()->is($child))->toBeFalse();
    expect($parent->attachment()->isNot($child))->toBeTrue();
});

test('child is model', function () {
    $parent = Post::first();
    $child = Attachment::first();

    expect($parent->attachment()->is($child))->toBeTrue();
    expect($parent->attachment()->isNot($child))->toBeFalse();
});

test('child is not another model', function () {
    $parent = Post::first();
    $child = new Attachment();
    $child->id = 2;

    expect($parent->attachment()->is($child))->toBeFalse();
    expect($parent->attachment()->isNot($child))->toBeTrue();
});

test('null child is not model', function () {
    $parent = Post::first();
    $child = Attachment::first();
    $child->attachable_type = null;
    $child->attachable_id = null;

    expect($parent->attachment()->is($child))->toBeFalse();
    expect($parent->attachment()->isNot($child))->toBeTrue();
});

test('child is not model with another table', function () {
    $parent = Post::first();
    $child = Attachment::first();
    $child->setTable('foo');

    expect($parent->attachment()->is($child))->toBeFalse();
    expect($parent->attachment()->isNot($child))->toBeTrue();
});

test('child is not model with another connection', function () {
    $parent = Post::first();
    $child = Attachment::first();
    $child->setConnection('foo');

    expect($parent->attachment()->is($child))->toBeFalse();
    expect($parent->attachment()->isNot($child))->toBeTrue();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->timestamps();
    });

    Schema::create('attachments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('attachable_type')->nullable();
        $table->integer('attachable_id')->nullable();
    });

    $post = Post::create();
    $post->attachment()->create();
}

function attachment()
{
    return test()->morphOne(Attachment::class, 'attachable');
}
