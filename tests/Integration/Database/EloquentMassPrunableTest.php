<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

uses()->group('SkipMSSQL');
use Mockery as m;

/**/
beforeEach(function () {
    Container::setInstance($container = new Container());

    $container->singleton(Dispatcher::class, function () {
        return m::mock(Dispatcher::class);
    });

    $container->alias(Dispatcher::class, 'events');
});

afterEach(function () {
    Container::setInstance(null);

    m::close();
});

test('prunable method must be implemented', function () {
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage(
        'Please implement',
    );

    MassPrunableTestModelMissingPrunableMethod::create()->pruneAll();
});

test('prunes records', function () {
    app('events')
        ->shouldReceive('dispatch')
        ->times(2)
        ->with(m::type(ModelsPruned::class));

    collect(range(1, 5000))->map(function ($id) {
        return ['id' => $id];
    })->chunk(200)->each(function ($chunk) {
        MassPrunableTestModel::insert($chunk->all());
    });

    $count = (new MassPrunableTestModel())->pruneAll();

    expect($count)->toEqual(1500);
    expect(MassPrunableTestModel::count())->toEqual(3500);
});

test('prunes soft deleted records', function () {
    app('events')
        ->shouldReceive('dispatch')
        ->times(3)
        ->with(m::type(ModelsPruned::class));

    collect(range(1, 5000))->map(function ($id) {
        return ['id' => $id, 'deleted_at' => now()];
    })->chunk(200)->each(function ($chunk) {
        MassPrunableSoftDeleteTestModel::insert($chunk->all());
    });

    $count = (new MassPrunableSoftDeleteTestModel())->pruneAll();

    expect($count)->toEqual(3000);
    expect(MassPrunableSoftDeleteTestModel::count())->toEqual(0);
    expect(MassPrunableSoftDeleteTestModel::withTrashed()->count())->toEqual(2000);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    collect([
        'mass_prunable_test_models',
        'mass_prunable_soft_delete_test_models',
        'mass_prunable_test_model_missing_prunable_methods',
    ])->each(function ($table) {
        Schema::create($table, function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
            $table->boolean('pruned')->default(false);
            $table->timestamps();
        });
    });
}

function prunable()
{
    return test()->where('id', '<=', 3000);
}
