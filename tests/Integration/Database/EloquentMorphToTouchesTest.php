<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database\EloquentMorphToTouchesTest;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphToTouchesTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->nullableMorphs('commentable');
        });

        Post::create();
    }

    public function test_not_null()
    {
        $comment = (new Comment())->commentable()->associate(Post::first());

        DB::enableQueryLog();

        $comment->save();

        $this->assertCount(2, DB::getQueryLog());
    }

    public function test_null()
    {
        DB::enableQueryLog();

        Comment::create();

        $this->assertCount(1, DB::getQueryLog());
    }
}

class Comment extends Model
{
    public $timestamps = false;

    protected $touches = ['commentable'];

    public function commentable()
    {
        return $this->morphTo(null, null, null, 'id');
    }
}

class Post extends Model
{
    //
}
