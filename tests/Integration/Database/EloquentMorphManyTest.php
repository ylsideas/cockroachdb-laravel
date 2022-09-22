<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentMorphManyTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('commentable_id');
            $table->string('commentable_type');
            $table->timestamps();
        });
    }

    public function test_update_model_with_default_with_count()
    {
        $post = MorphManyPost::create(['title' => Str::random()]);

        $post->update(['title' => 'new name']);

        $this->assertSame('new name', $post->title);
    }

    public function test_self_referencing_existence_query()
    {
        $post = MorphManyPost::create(['title' => 'foo']);

        $comment = tap((new MorphManyComment(['id' => 1, 'name' => 'foo']))->commentable()->associate($post))->save();

        (new MorphManyComment(['id' => 2, 'name' => 'bar']))->commentable()->associate($comment)->save();

        $comments = MorphManyComment::has('replies')->get();

        $this->assertEquals([1], $comments->pluck('id')->all());
    }
}

class MorphManyPost extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];
    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(MorphManyComment::class, 'commentable');
    }
}

class MorphManyComment extends Model
{
    public $table = 'comments';
    public $timestamps = true;
    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function replies()
    {
        return $this->morphMany(self::class, 'commentable');
    }
}
