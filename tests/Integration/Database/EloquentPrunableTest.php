<?php

use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

uses()->group('SkipMSSQL');

/**/
test('prunable method must be implemented', function () {
    $this->expectException(LogicException::class);
    $this->expectExceptionMessage(
        'Please implement',
    );

    PrunableTestModelMissingPrunableMethod::create()->pruneAll();
});

test('prunes records', function () {
    Event::fake();

    collect(range(1, 5000))->map(function ($id) {
        return ['id' => $id];
    })->chunk(200)->each(function ($chunk) {
        PrunableTestModel::insert($chunk->all());
    });

    $count = (new PrunableTestModel())->pruneAll();

    expect($count)->toEqual(1500);
    expect(PrunableTestModel::count())->toEqual(3500);

    Event::assertDispatched(ModelsPruned::class, 2);
});

test('prunes soft deleted records', function () {
    Event::fake();

    collect(range(1, 5000))->map(function ($id) {
        return ['id' => $id, 'deleted_at' => now()];
    })->chunk(200)->each(function ($chunk) {
        PrunableSoftDeleteTestModel::insert($chunk->all());
    });

    $count = (new PrunableSoftDeleteTestModel())->pruneAll();

    expect($count)->toEqual(3000);
    expect(PrunableSoftDeleteTestModel::count())->toEqual(0);
    expect(PrunableSoftDeleteTestModel::withTrashed()->count())->toEqual(2000);

    Event::assertDispatched(ModelsPruned::class, 3);
});

test('prune with custom prune method', function () {
    Event::fake();

    collect(range(1, 5000))->map(function ($id) {
        return ['id' => $id];
    })->chunk(200)->each(function ($chunk) {
        PrunableWithCustomPruneMethodTestModel::insert($chunk->all());
    });

    $count = (new PrunableWithCustomPruneMethodTestModel())->pruneAll();

    expect($count)->toEqual(1000);
    expect((bool) PrunableWithCustomPruneMethodTestModel::first()->pruned)->toBeTrue();
    expect((bool) PrunableWithCustomPruneMethodTestModel::orderBy('id', 'desc')->first()->pruned)->toBeFalse();
    expect(PrunableWithCustomPruneMethodTestModel::count())->toEqual(5000);

    Event::assertDispatched(ModelsPruned::class, 1);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    collect([
        'prunable_test_models',
        'prunable_soft_delete_test_models',
        'prunable_test_model_missing_prunable_methods',
        'prunable_with_custom_prune_method_test_models',
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
    return test()->where('id', '<=', 1000);
}

function prune()
{
    test()->forceFill([
        'pruned' => true,
    ])->save();
}
