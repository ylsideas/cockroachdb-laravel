<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

beforeEach(function () {
    Model::preventLazyLoading();
});

test('strict mode throws an exception on lazy loading', function () {
    $this->expectException(LazyLoadingViolationException::class);
    $this->expectExceptionMessage('Attempted to lazy load');

    EloquentStrictLoadingTestModel1::create();
    EloquentStrictLoadingTestModel1::create();

    $models = EloquentStrictLoadingTestModel1::get();

    $models[0]->modelTwos;
});

test('strict mode doesnt throw an exception on lazy loading with single model', function () {
    EloquentStrictLoadingTestModel1::create();

    $models = EloquentStrictLoadingTestModel1::get();

    expect($models)->toBeInstanceOf(Collection::class);
});

test('strict mode doesnt throw an exception on attributes', function () {
    EloquentStrictLoadingTestModel1::create();

    $models = EloquentStrictLoadingTestModel1::get(['id']);

    expect($models[0]->number)->toBeNull();
});

test('strict mode doesnt throw an exception on eager loading', function () {
    app()['config']->set('database.connections.testing.zxc', false);

    EloquentStrictLoadingTestModel1::create();
    EloquentStrictLoadingTestModel1::create();

    $models = EloquentStrictLoadingTestModel1::with('modelTwos')->get();

    expect($models[0]->modelTwos)->toBeInstanceOf(Collection::class);
});

test('strict mode doesnt throw an exception on lazy eager loading', function () {
    EloquentStrictLoadingTestModel1::create();
    EloquentStrictLoadingTestModel1::create();

    $models = EloquentStrictLoadingTestModel1::get();

    $models->load('modelTwos');

    expect($models[0]->modelTwos)->toBeInstanceOf(Collection::class);
});

test('strict mode doesnt throw an exception on single model loading', function () {
    $model = EloquentStrictLoadingTestModel1::create();

    $model = EloquentStrictLoadingTestModel1::find($model->id);

    expect($model->modelTwos)->toBeInstanceOf(Collection::class);
});

test('strict mode throws an exception on lazy loading in relations', function () {
    $this->expectException(LazyLoadingViolationException::class);
    $this->expectExceptionMessage('Attempted to lazy load');

    $model1 = EloquentStrictLoadingTestModel1::create();
    EloquentStrictLoadingTestModel2::create(['model_1_id' => $model1->id]);
    EloquentStrictLoadingTestModel2::create(['model_1_id' => $model1->id]);

    $models = EloquentStrictLoadingTestModel1::with('modelTwos')->get();

    $models[0]->modelTwos[0]->modelThrees;
});

test('strict mode with custom callback on lazy loading', function () {
    $this->expectsEvents(ViolatedLazyLoadingEvent::class);

    Model::handleLazyLoadingViolationUsing(function ($model, $key) {
        event(new ViolatedLazyLoadingEvent($model, $key));
    });

    EloquentStrictLoadingTestModel1::create();
    EloquentStrictLoadingTestModel1::create();

    $models = EloquentStrictLoadingTestModel1::get();

    $models[0]->modelTwos;

    EloquentStrictLoadingTestModel1::resetCustomCallback();
});

test('strict mode with overridden handler on lazy loading', function () {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Violated');

    EloquentStrictLoadingTestModel1WithCustomHandler::create();
    EloquentStrictLoadingTestModel1WithCustomHandler::create();

    $models = EloquentStrictLoadingTestModel1WithCustomHandler::get();

    $models[0]->modelTwos;
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('strict_loading_test_model1', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('number')->default(1);
    });

    Schema::create('strict_loading_test_model2', function (Blueprint $table) {
        $table->increments('id');
        $table->foreignId('model_1_id');
    });

    Schema::create('strict_loading_test_model3', function (Blueprint $table) {
        $table->increments('id');
        $table->foreignId('model_2_id');
    });
}

function resetCustomCallback()
{
    self::$lazyLoadingViolationCallback = null;
}

function modelTwos()
{
    return test()->hasMany(EloquentStrictLoadingTestModel2::class, 'model_1_id');
}

function handleLazyLoadingViolation($key)
{
    throw new \RuntimeException("Violated {$key}");
}

function modelThrees()
{
    return test()->hasMany(EloquentStrictLoadingTestModel3::class, 'model_2_id');
}

function __construct($model, $key)
{
    test()->model = $model;
    test()->key = $key;
}
