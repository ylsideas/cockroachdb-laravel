<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('refreshes model excluded by global scope', function () {
    $post = Post::create(['title' => 'mohamed']);

    $post->refresh();
});

it('refreshes a soft deleted model', function () {
    $post = Post::create(['title' => 'said']);

    Post::find($post->id)->delete();

    expect($post->trashed())->toBeFalse();

    $post->refresh();

    expect($post->trashed())->toBeTrue();
});

it('syncs original on refresh', function () {
    $post = Post::create(['title' => 'pat']);

    Post::find($post->id)->update(['title' => 'patrick']);

    $post->refresh();

    expect($post->getDirty())->toBeEmpty();

    expect($post->getOriginal('title'))->toBe('patrick');
});

test('as pivot', function () {
    Schema::create('post_posts', function (Blueprint $table) {
        $table->increments('id');
        $table->bigInteger('foreign_id');
        $table->bigInteger('related_id');
    });

    $post = AsPivotPost::create(['title' => 'parent']);
    $child = AsPivotPost::create(['title' => 'child']);

    $post->children()->attach($child->getKey());

    expect($post->children->count())->toEqual(1);

    $post->children->first()->refresh();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->timestamps();
        $table->softDeletes();
    });
}

function boot()
{
    parent::boot();

    static::addGlobalScope('age', function ($query) {
        $query->where('title', '!=', 'mohamed');
    });
}

function children()
{
    return test()
        ->belongsToMany(static::class, (new AsPivotPostPivot())->getTable(), 'foreign_id', 'related_id')
        ->using(AsPivotPostPivot::class);
}
