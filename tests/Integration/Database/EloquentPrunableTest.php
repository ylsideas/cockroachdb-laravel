<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use LogicException;

/** @group SkipMSSQL */
class EloquentPrunableTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
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

    public function test_prunable_method_must_be_implemented()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Please implement',
        );

        PrunableTestModelMissingPrunableMethod::create()->pruneAll();
    }

    public function test_prunes_records()
    {
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
    }

    public function test_prunes_soft_deleted_records()
    {
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
    }

    public function test_prune_with_custom_prune_method()
    {
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
    }
}

class PrunableTestModel extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1500);
    }
}

class PrunableSoftDeleteTestModel extends Model
{
    use Prunable;
    use SoftDeletes;

    public function prunable()
    {
        return $this->where('id', '<=', 3000);
    }
}

class PrunableWithCustomPruneMethodTestModel extends Model
{
    use Prunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1000);
    }

    public function prune()
    {
        $this->forceFill([
            'pruned' => true,
        ])->save();
    }
}

class PrunableTestModelMissingPrunableMethod extends Model
{
    use Prunable;
}
