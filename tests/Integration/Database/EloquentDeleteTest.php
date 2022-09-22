<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentDeleteTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('body')->nullable();
            $table->integer('post_id');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /** @group SkipMSSQL */
    public function test_delete_with_limit()
    {
        for ($i = 1; $i <= 10; $i++) {
            CommentDelete::create([
                'id' => $i,
                'post_id' => PostDelete::create(['id' => $i])->id,
            ]);
        }

        PostDelete::latest('id')->limit(1)->delete();
        $this->assertCount(9, PostDelete::all());

        PostDelete::query()
            ->whereIn(
                'posts.id',
                CommentDelete::query()
                    ->select('comments.post_id')
                    ->whereColumn('posts.id', '=', 'comments.post_id')
            )
            ->where('posts.id', '>', 8)
            ->orderBy('posts.id')
            ->limit(1)
            ->delete();
        $this->assertCount(8, PostDelete::all());
    }

    public function test_force_deleted_event_is_fired()
    {
        $role = Role::create([]);
        $this->assertInstanceOf(Role::class, $role);
        Role::observe(new RoleObserver());

        $role->delete();
        $this->assertNull(RoleObserver::$model);

        $role->forceDelete();

        $this->assertEquals($role->id, RoleObserver::$model->id);
    }
}

class PostDelete extends Model
{
    public $table = 'posts';
    protected $guarded = [];
}

class CommentDelete extends Model
{
    public $table = 'comments';
    protected $guarded = [];
}

class Role extends Model
{
    use SoftDeletes;
    public $table = 'roles';
    protected $guarded = [];
}

class RoleObserver
{
    public static $model;

    public function forceDeleted($model)
    {
        static::$model = $model;
    }
}
