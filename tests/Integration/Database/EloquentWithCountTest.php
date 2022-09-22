<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentWithCountTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('one', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('two', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('three', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('two_id');
        });

        Schema::create('four', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });
    }

    public function test_it_basic()
    {
        $one = Model1::create(['id' => 1]);
        $two = $one->twos()->Create(['id' => 2]);
        $two->threes()->Create(['id' => 3]);

        $results = Model1::withCount([
            'twos' => function ($query) {
                $query->where('id', '>=', 1);
            },
        ]);

        $this->assertEquals([
            ['id' => 1, 'twos_count' => 1],
        ], $results->get()->toArray());
    }

    public function test_global_scopes()
    {
        $one = Model1::create(['id' => 1]);
        $one->fours()->create(['id' => 1]);

        $result = Model1::withCount('fours')->first();
        $this->assertEquals(0, $result->fours_count);

        $result = Model1::withCount('allFours')->first();
        $this->assertEquals(1, $result->all_fours_count);
    }

    public function test_sorting_scopes()
    {
        $one = Model1::create(['id' => 1]);
        $one->twos()->create();

        $query = Model1::withCount('twos')->getQuery();

        $this->assertNull($query->orders);
        $this->assertSame([], $query->getRawBindings()['order']);
    }
}

class Model1 extends Model
{
    public $table = 'one';
    public $timestamps = false;
    protected $guarded = [];

    public function twos()
    {
        return $this->hasMany(Model2::class, 'one_id');
    }

    public function fours()
    {
        return $this->hasMany(Model4::class, 'one_id');
    }

    public function allFours()
    {
        return $this->fours()->withoutGlobalScopes();
    }
}

class Model2 extends Model
{
    public $table = 'two';
    public $timestamps = false;
    protected $guarded = [];
    protected $withCount = ['threes'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->latest();
        });
    }

    public function threes()
    {
        return $this->hasMany(Model3::class, 'two_id');
    }
}

class Model3 extends Model
{
    public $table = 'three';
    public $timestamps = false;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->where('id', '>', 0);
        });
    }
}

class Model4 extends Model
{
    public $table = 'four';
    public $timestamps = false;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->where('id', '>', 1);
        });
    }
}
