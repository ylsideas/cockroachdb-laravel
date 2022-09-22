<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelRefreshTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_it_refreshes_model_excluded_by_global_scope()
    {
        /** @var ModelRefreshPost $post */
        $post = ModelRefreshPost::create(['title' => 'mohamed']);

        $this->assertInstanceOf(ModelRefreshPost::class, $post->refresh());
    }

    public function test_it_refreshes_a_soft_deleted_model()
    {
        $post = ModelRefreshPost::create(['title' => 'said']);

        ModelRefreshPost::find($post->id)->delete();

        $this->assertFalse($post->trashed());

        $post->refresh();

        $this->assertTrue($post->trashed());
    }

    public function test_it_syncs_original_on_refresh()
    {
        $post = ModelRefreshPost::create(['title' => 'pat']);

        ModelRefreshPost::find($post->id)->update(['title' => 'patrick']);

        $post->refresh();

        $this->assertEmpty($post->getDirty());

        $this->assertSame('patrick', $post->getOriginal('title'));
    }

    public function test_as_pivot()
    {
        Schema::create('post_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('foreign_id');
            $table->bigInteger('related_id');
        });

        $post = AsPivotModelRefreshPost::create(['title' => 'parent']);
        $child = AsPivotModelRefreshPost::create(['title' => 'child']);

        $post->children()->attach($child->getKey());

        $this->assertEquals(1, $post->children->count());

        $post->children->first()->refresh();
    }
}

class ModelRefreshPost extends Model
{
    use SoftDeletes;
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('age', function ($query) {
            $query->where('title', '!=', 'mohamed');
        });
    }
}

class AsPivotModelRefreshPost extends ModelRefreshPost
{
    public function children()
    {
        return $this
            ->belongsToMany(static::class, (new AsPivotPostPivot())->getTable(), 'foreign_id', 'related_id')
            ->using(AsPivotPostPivot::class);
    }
}

class AsPivotPostPivot extends Model
{
    use AsPivot;

    protected $table = 'post_posts';
}
