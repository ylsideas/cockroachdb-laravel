<?php

namespace App\Integration\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

class EloquentCollectionLoadCountTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
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

    public function test_load_count()
    {
        $posts = Post::all();

        DB::enableQueryLog();

        $posts->loadCount('comments');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('2', $posts[0]->comments_count);
        $this->assertEquals('0', $posts[1]->comments_count);
        $this->assertEquals('2', $posts[0]->getOriginal('comments_count'));
    }

    public function test_load_count_with_same_models()
    {
        $posts = Post::all()->push(Post::first());

        DB::enableQueryLog();

        $posts->loadCount('comments');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('2', $posts[0]->comments_count);
        $this->assertEquals('0', $posts[1]->comments_count);
        $this->assertEquals('2', $posts[2]->comments_count);
    }

    public function test_load_count_on_deleted_models()
    {
        $posts = Post::all()->each->delete();

        DB::enableQueryLog();

        $posts->loadCount('comments');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('2', $posts[0]->comments_count);
        $this->assertEquals('0', $posts[1]->comments_count);
    }

    public function test_load_count_with_array_of_relations()
    {
        $posts = Post::all();

        DB::enableQueryLog();

        $posts->loadCount(['comments', 'likes']);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('2', $posts[0]->comments_count);
        $this->assertEquals('1', $posts[0]->likes_count);
        $this->assertEquals('0', $posts[1]->comments_count);
        $this->assertEquals('0', $posts[1]->likes_count);
    }

    public function test_load_count_does_not_override_attributes_with_default_value()
    {
        $post = Post::first();
        $post->some_default_value = 200;

        Collection::make([$post])->loadCount('comments');

        $this->assertSame(200, $post->some_default_value);
        $this->assertEquals('2', $post->comments_count);
    }
}

class Post extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'some_default_value' => 100,
    ];

    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}

class Comment extends Model
{
    public $timestamps = false;
}

class Like extends Model
{
    public $timestamps = false;
}
