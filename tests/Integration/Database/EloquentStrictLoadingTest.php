<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class EloquentStrictLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ResetCustomLoading::resetCustomLazyLoadingEvent();
        Model::preventLazyLoading();
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('number')->default(1);
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('model_1_id');
        });

        Schema::create('test_model3', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('model_2_id');
        });
    }

    public function test_strict_mode_throws_an_exception_on_lazy_loading()
    {
        $this->expectException(LazyLoadingViolationException::class);
        $this->expectExceptionMessage('Attempted to lazy load');

        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $models[0]->modelTwos;
    }

    public function test_strict_mode_doesnt_throw_an_exception_on_lazy_loading_with_single_model()
    {
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $this->assertInstanceOf(Collection::class, $models);
    }

    public function test_strict_mode_doesnt_throw_an_exception_on_attributes()
    {
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get(['id']);

        $this->assertNull($models[0]->number);
    }

    public function test_strict_mode_doesnt_throw_an_exception_on_eager_loading()
    {
        $this->app['config']->set('database.connections.testing.zxc', false);

        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::with('modelTwos')->get();

        $this->assertInstanceOf(Collection::class, $models[0]->modelTwos);
    }

    public function test_strict_mode_doesnt_throw_an_exception_on_lazy_eager_loading()
    {
        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $models->load('modelTwos');

        $this->assertInstanceOf(Collection::class, $models[0]->modelTwos);
    }

    public function test_strict_mode_doesnt_throw_an_exception_on_single_model_loading()
    {
        $model = EloquentStrictLoadingTestModel1::create();

        $model = EloquentStrictLoadingTestModel1::find($model->id);

        $this->assertInstanceOf(Collection::class, $model->modelTwos);
    }

    public function test_strict_mode_throws_an_exception_on_lazy_loading_in_relations()
    {
        $this->expectException(LazyLoadingViolationException::class);
        $this->expectExceptionMessage('Attempted to lazy load');

        $model1 = EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel2::create(['model_1_id' => $model1->id]);
        EloquentStrictLoadingTestModel2::create(['model_1_id' => $model1->id]);

        $models = EloquentStrictLoadingTestModel1::with('modelTwos')->get();

        $models[0]->modelTwos[0]->modelThrees;
    }

    public function test_strict_mode_with_custom_callback_on_lazy_loading()
    {
        Event::fake();

        Model::handleLazyLoadingViolationUsing(function ($model, $key) {
            event(new ViolatedLazyLoadingEvent($model, $key));
        });

        EloquentStrictLoadingTestModel1::create();
        EloquentStrictLoadingTestModel1::create();

        $models = EloquentStrictLoadingTestModel1::get();

        $models[0]->modelTwos;

        Event::assertDispatched(ViolatedLazyLoadingEvent::class);
    }

    public function test_strict_mode_with_overridden_handler_on_lazy_loading()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Violated');

        EloquentStrictLoadingTestModel1WithCustomHandler::create();
        EloquentStrictLoadingTestModel1WithCustomHandler::create();

        $models = EloquentStrictLoadingTestModel1WithCustomHandler::get();

        $models[0]->modelTwos;
    }
}

class ResetCustomLoading extends Model
{
    public static function resetCustomLazyLoadingEvent()
    {
        Model::$lazyLoadingViolationCallback = null;
    }
}

class EloquentStrictLoadingTestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public function modelTwos()
    {
        return $this->hasMany(EloquentStrictLoadingTestModel2::class, 'model_1_id');
    }
}

class EloquentStrictLoadingTestModel1WithCustomHandler extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public function modelTwos()
    {
        return $this->hasMany(EloquentStrictLoadingTestModel2::class, 'model_1_id');
    }

    protected function handleLazyLoadingViolation($key)
    {
        throw new RuntimeException("Violated {$key}");
    }
}

class EloquentStrictLoadingTestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = [];

    public function modelThrees()
    {
        return $this->hasMany(EloquentStrictLoadingTestModel3::class, 'model_2_id');
    }
}

class EloquentStrictLoadingTestModel3 extends Model
{
    public $table = 'test_model3';
    public $timestamps = false;
    protected $guarded = [];
}

class ViolatedLazyLoadingEvent
{
    public $model;
    public $key;

    public function __construct($model, $key)
    {
        $this->model = $model;
        $this->key = $key;
    }
}
