<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Events\ModelsPruned;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Mockery as m;

/** @group SkipMSSQL */
class EloquentMassPrunableTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($container = new Container());

        $container->singleton(Dispatcher::class, function () {
            return m::mock(Dispatcher::class);
        });

        $container->alias(Dispatcher::class, 'events');
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
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

    public function test_prunable_method_must_be_implemented()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Please implement',
        );

        MassPrunableTestModelMissingPrunableMethod::create()->pruneAll();
    }

    public function test_prunes_records()
    {
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

        $this->assertEquals(1500, $count);
        $this->assertEquals(3500, MassPrunableTestModel::count());
    }

    public function test_prunes_soft_deleted_records()
    {
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

        $this->assertEquals(3000, $count);
        $this->assertEquals(0, MassPrunableSoftDeleteTestModel::count());
        $this->assertEquals(2000, MassPrunableSoftDeleteTestModel::withTrashed()->count());
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Container::setInstance(null);

        m::close();
    }
}

class MassPrunableTestModel extends Model
{
    use MassPrunable;

    public function prunable()
    {
        return $this->where('id', '<=', 1500);
    }
}

class MassPrunableSoftDeleteTestModel extends Model
{
    use MassPrunable;
    use SoftDeletes;

    public function prunable()
    {
        return $this->where('id', '<=', 3000);
    }
}

class MassPrunableTestModelMissingPrunableMethod extends Model
{
    use MassPrunable;
}
