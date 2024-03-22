<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentEagerLoadingLimitTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
        });

        EagerLoadLimitUser::create(['id' => 100]);
        EagerLoadLimitUser::create(['id' => 200]);

        EagerLoadLimitPost::create(['id' => 100, 'user_id' => 100, 'created_at' => new Carbon('2024-01-01 00:00:01')]);
        EagerLoadLimitPost::create(['id' => 200, 'user_id' => 100, 'created_at' => new Carbon('2024-01-01 00:00:02')]);
        EagerLoadLimitPost::create(['id' => 300, 'user_id' => 100, 'created_at' => new Carbon('2024-01-01 00:00:03')]);
        EagerLoadLimitPost::create(['id' => 400, 'user_id' => 200, 'created_at' => new Carbon('2024-01-01 00:00:04')]);
        EagerLoadLimitPost::create(['id' => 500, 'user_id' => 200, 'created_at' => new Carbon('2024-01-01 00:00:05')]);
        EagerLoadLimitPost::create(['id' => 600, 'user_id' => 200, 'created_at' => new Carbon('2024-01-01 00:00:06')]);

        EagerLoadLimitComment::create(['id' => 100, 'post_id' => 100, 'created_at' => new Carbon('2024-01-01 00:00:01')]);
        EagerLoadLimitComment::create(['id' => 200, 'post_id' => 200, 'created_at' => new Carbon('2024-01-01 00:00:02')]);
        EagerLoadLimitComment::create(['id' => 300, 'post_id' => 300, 'created_at' => new Carbon('2024-01-01 00:00:03')]);
        EagerLoadLimitComment::create(['id' => 400, 'post_id' => 400, 'created_at' => new Carbon('2024-01-01 00:00:04')]);
        EagerLoadLimitComment::create(['id' => 500, 'post_id' => 500, 'created_at' => new Carbon('2024-01-01 00:00:05')]);
        EagerLoadLimitComment::create(['id' => 600, 'post_id' => 600, 'created_at' => new Carbon('2024-01-01 00:00:06')]);

        EagerLoadLimitRole::create(['id' => 100, 'created_at' => new Carbon('2024-01-01 00:00:01')]);
        EagerLoadLimitRole::create(['id' => 200, 'created_at' => new Carbon('2024-01-01 00:00:02')]);
        EagerLoadLimitRole::create(['id' => 300, 'created_at' => new Carbon('2024-01-01 00:00:03')]);
        EagerLoadLimitRole::create(['id' => 400, 'created_at' => new Carbon('2024-01-01 00:00:04')]);
        EagerLoadLimitRole::create(['id' => 500, 'created_at' => new Carbon('2024-01-01 00:00:05')]);
        EagerLoadLimitRole::create(['id' => 600, 'created_at' => new Carbon('2024-01-01 00:00:06')]);

        DB::table('role_user')->insert([
            ['role_id' => 100, 'user_id' => 100],
            ['role_id' => 200, 'user_id' => 100],
            ['role_id' => 300, 'user_id' => 100],
            ['role_id' => 400, 'user_id' => 200],
            ['role_id' => 500, 'user_id' => 200],
            ['role_id' => 600, 'user_id' => 200],
        ]);
    }

    public function testBelongsToMany(): void
    {
        $users = EagerLoadLimitUser::with(['roles' => fn ($query) => $query->latest()->limit(2)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([300, 200], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([600, 500], $users[1]->roles->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_row', $users[0]->roles[0]);
        $this->assertArrayNotHasKey('@laravel_group := `user_id`', $users[0]->roles[0]);
    }

    public function testBelongsToManyWithOffset(): void
    {
        $users = EagerLoadLimitUser::with(['roles' => fn ($query) => $query->latest()->limit(2)->offset(1)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([200, 100], $users[0]->roles->pluck('id')->all());
        $this->assertEquals([500, 400], $users[1]->roles->pluck('id')->all());
    }

    public function testHasMany(): void
    {
        $users = EagerLoadLimitUser::with(['posts' => fn ($query) => $query->latest()->limit(2)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([300, 200], $users[0]->posts->pluck('id')->all());
        $this->assertEquals([600, 500], $users[1]->posts->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_row', $users[0]->posts[0]);
        $this->assertArrayNotHasKey('@laravel_group := `user_id`', $users[0]->posts[0]);
    }

    public function testHasManyWithOffset(): void
    {
        $users = EagerLoadLimitUser::with(['posts' => fn ($query) => $query->latest()->limit(2)->offset(1)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([200, 100], $users[0]->posts->pluck('id')->all());
        $this->assertEquals([500, 400], $users[1]->posts->pluck('id')->all());
    }

    public function testHasManyThrough(): void
    {
        $users = EagerLoadLimitUser::with(['comments' => fn ($query) => $query->latest('comments.created_at')->limit(2)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([300, 200], $users[0]->comments->pluck('id')->all());
        $this->assertEquals([600, 500], $users[1]->comments->pluck('id')->all());
        $this->assertArrayNotHasKey('laravel_row', $users[0]->comments[0]);
        $this->assertArrayNotHasKey('@laravel_group := `user_id`', $users[0]->comments[0]);
    }

    public function testHasManyThroughWithOffset(): void
    {
        $users = EagerLoadLimitUser::with(['comments' => fn ($query) => $query->latest('comments.created_at')->limit(2)->offset(1)])
            ->orderBy('id')
            ->get();

        $this->assertEquals([200, 100], $users[0]->comments->pluck('id')->all());
        $this->assertEquals([500, 400], $users[1]->comments->pluck('id')->all());
    }
}

class EagerLoadLimitComment extends Model
{
    protected $table = 'comments';
    public $timestamps = false;

    protected $guarded = [];
}

class EagerLoadLimitPost extends Model
{
    protected $table = 'posts';
    protected $guarded = [];
}

class EagerLoadLimitRole extends Model
{
    protected $table = 'roles';
    protected $guarded = [];
}

class EagerLoadLimitUser extends Model
{
    protected $table = 'users';
    public $timestamps = false;

    protected $guarded = [];

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(EagerLoadLimitComment::class, EagerLoadLimitPost::class, 'user_id', 'post_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(EagerLoadLimitPost::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(EagerLoadLimitRole::class, 'role_user', 'user_id', 'role_id');
    }
}
