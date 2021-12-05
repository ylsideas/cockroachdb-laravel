<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentMorphToSelectTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
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

    public function testSelect()
    {
        $comments = CommentMorphTest::with('commentable:id')->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function testSelectRaw()
    {
        $comments = CommentMorphTest::with(['commentable' => function ($query) {
            $query->selectRaw('id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function testSelectSub()
    {
        $comments = CommentMorphTest::with(['commentable' => function ($query) {
            $query->selectSub(function ($query) {
                $query->select('id');
            }, 'id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function testAddSelect()
    {
        $comments = CommentMorphTest::with(['commentable' => function ($query) {
            $query->addSelect('id');
        }])->get();

        $this->assertEquals(['id' => 1], $comments[0]->commentable->getAttributes());
    }

    public function testLazyLoading()
    {
        $comment = CommentMorphTest::first();
        $post = $comment->commentable()->select('id')->first();

        $this->assertEquals(['id' => 1], $post->getAttributes());
    }
}

class CommentMorphTest extends Model
{
    protected $guarded = [];
    protected $table = 'comments';
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }
}

class PostMorphTest extends Model
{
    protected $table = 'posts';
    protected $guarded = [];
}
