<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database\EloquentWhereHasMorphTest;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

class EloquentWhereHasMorphTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->softDeletes();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('commentable');
            $table->softDeletes();
        });

        $models = [];

        $models[] = Post::create(['id' => 1, 'title' => 'foo']);
        $models[] = Post::create(['id' => 2, 'title' => 'bar']);
        $models[] = Post::create(['id' => 3, 'title' => 'baz']);
        end($models)->delete();

        $models[] = Video::create(['id' => 1, 'title' => 'foo']);
        $models[] = Video::create(['id' => 2, 'title' => 'bar']);
        $models[] = Video::create(['id' => 3, 'title' => 'baz']);

        $i = 0;

        foreach ($models as $model) {
            $i++;
            (new Comment())->setAttribute('id', $i)->commentable()->associate($model)->save();
        }
    }

    public function test_where_has_morph()
    {
        $comments = Comment::whereHasMorph('commentable', [Post::class, Video::class], function (Builder $query) {
            $query->where('title', 'foo');
        })->orderBy('id')->get();

        $this->assertEquals([1, 4], $comments->pluck('id')->all());
    }

    public function test_where_has_morph_with_morph_map()
    {
        Relation::morphMap(['posts' => Post::class]);

        Comment::where('commentable_type', Post::class)->update(['commentable_type' => 'posts']);

        try {
            $comments = Comment::whereHasMorph('commentable', [Post::class, Video::class], function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

            $this->assertEquals([1, 4], $comments->pluck('id')->all());
        } finally {
            Relation::morphMap([], false);
        }
    }

    public function test_where_has_morph_with_wildcard()
    {
        // Test newModelQuery() without global scopes.
        Comment::where('commentable_type', Video::class)->delete();

        $comments = Comment::withTrashed()
            ->whereHasMorph('commentable', '*', function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

        $this->assertEquals([1, 4], $comments->pluck('id')->all());
    }

    public function test_where_has_morph_with_wildcard_and_morph_map()
    {
        Relation::morphMap(['posts' => Post::class]);

        Comment::where('commentable_type', Post::class)->update(['commentable_type' => 'posts']);

        try {
            $comments = Comment::whereHasMorph('commentable', '*', function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

            $this->assertEquals([1, 4], $comments->pluck('id')->all());
        } finally {
            Relation::morphMap([], false);
        }
    }

    public function test_where_has_morph_with_relation_constraint()
    {
        $comments = Comment::whereHasMorph('commentableWithConstraint', Video::class, function (Builder $query) {
            $query->where('title', 'like', 'ba%');
        })->orderBy('id')->get();

        $this->assertEquals([5], $comments->pluck('id')->all());
    }

    public function test_where_has_morph_wit_different_constraints()
    {
        $comments = Comment::whereHasMorph('commentable', [Post::class, Video::class], function (Builder $query, $type) {
            if ($type === Post::class) {
                $query->where('title', 'foo');
            }

            if ($type === Video::class) {
                $query->where('title', 'bar');
            }
        })->orderBy('id')->get();

        $this->assertEquals([1, 5], $comments->pluck('id')->all());
    }

    public function test_where_has_morph_with_owner_key()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('slug')->nullable();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_commentable_type_commentable_id_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('commentable_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->string('commentable_id')->nullable();
        });

        Post::where('id', 1)->update(['slug' => 'foo']);

        Comment::where('id', 1)->update(['commentable_id' => 'foo']);

        $comments = Comment::whereHasMorph('commentableWithOwnerKey', Post::class, function (Builder $query) {
            $query->where('title', 'foo');
        })->orderBy('id')->get();

        $this->assertEquals([1], $comments->pluck('id')->all());
    }

    public function test_has_morph()
    {
        $comments = Comment::hasMorph('commentable', Post::class)->orderBy('id')->get();

        $this->assertEquals([1, 2], $comments->pluck('id')->all());
    }

    public function test_or_has_morph()
    {
        $comments = Comment::where('id', 1)->orHasMorph('commentable', Video::class)->orderBy('id')->get();

        $this->assertEquals([1, 4, 5, 6], $comments->pluck('id')->all());
    }

    public function test_doesnt_have_morph()
    {
        $comments = Comment::doesntHaveMorph('commentable', Post::class)->orderBy('id')->get();

        $this->assertEquals([3], $comments->pluck('id')->all());
    }

    public function test_or_doesnt_have_morph()
    {
        $comments = Comment::where('id', 1)->orDoesntHaveMorph('commentable', Post::class)->orderBy('id')->get();

        $this->assertEquals([1, 3], $comments->pluck('id')->all());
    }

    public function test_or_where_has_morph()
    {
        $comments = Comment::where('id', 1)
            ->orWhereHasMorph('commentable', Video::class, function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

        $this->assertEquals([1, 4], $comments->pluck('id')->all());
    }

    public function test_where_doesnt_have_morph()
    {
        $comments = Comment::whereDoesntHaveMorph('commentable', Post::class, function (Builder $query) {
            $query->where('title', 'foo');
        })->orderBy('id')->get();

        $this->assertEquals([2, 3], $comments->pluck('id')->all());
    }

    public function test_or_where_doesnt_have_morph()
    {
        $comments = Comment::where('id', 1)
            ->orWhereDoesntHaveMorph('commentable', Post::class, function (Builder $query) {
                $query->where('title', 'foo');
            })->orderBy('id')->get();

        $this->assertEquals([1, 2, 3], $comments->pluck('id')->all());
    }

    public function test_model_scopes_are_accessible()
    {
        $comments = Comment::whereHasMorph('commentable', [Post::class, Video::class], function (Builder $query) {
            $query->someSharedModelScope();
        })->orderBy('id')->get();

        $this->assertEquals([1, 4], $comments->pluck('id')->all());
    }
}

class Comment extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function commentableWithConstraint()
    {
        return $this->morphTo('commentable')->where('title', 'bar');
    }

    public function commentableWithOwnerKey()
    {
        return $this->morphTo('commentable', null, null, 'slug');
    }
}

class Post extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    public function scopeSomeSharedModelScope($query)
    {
        $query->where('title', '=', 'foo');
    }
}

class Video extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function scopeSomeSharedModelScope($query)
    {
        $query->where('title', '=', 'foo');
    }
}
