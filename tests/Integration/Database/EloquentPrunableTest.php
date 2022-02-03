<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    $this->assertEquals(1500, $count);
    $this->assertEquals(3500, PrunableTestModel::count());

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

    $this->assertEquals(3000, $count);
    $this->assertEquals(0, PrunableSoftDeleteTestModel::count());
    $this->assertEquals(2000, PrunableSoftDeleteTestModel::withTrashed()->count());

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

    $this->assertEquals(1000, $count);
    $this->assertTrue((bool) PrunableWithCustomPruneMethodTestModel::first()->pruned);
    $this->assertFalse((bool) PrunableWithCustomPruneMethodTestModel::orderBy('id', 'desc')->first()->pruned);
    $this->assertEquals(5000, PrunableWithCustomPruneMethodTestModel::count());

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
