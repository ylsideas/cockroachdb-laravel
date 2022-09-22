<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\WithMultipleApplicationVersions;

if (PHP_VERSION_ID >= 80100) {
    include 'Enums.php';
}

/**
 * @requires PHP >= 8.1
 */
class EloquentModelEnumCastingTest extends DatabaseTestCase
{
    use WithMultipleApplicationVersions;

    public function setUp(): void
    {
        parent::setUp();

        $this->skipIfOlderThan('8.75');
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('enum_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('string_status', 100)->nullable();
            $table->integer('integer_status')->nullable();
        });
    }

    public function test_enums_are_castable()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'integer_status' => 1,
        ]);

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(StringStatus::pending, $model->string_status);
        $this->assertEquals(IntegerStatus::pending, $model->integer_status);
    }

    public function test_enums_return_null_when_null()
    {
        DB::table('enum_casts')->insert([
            'string_status' => null,
            'integer_status' => null,
        ]);

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(null, $model->string_status);
        $this->assertEquals(null, $model->integer_status);
    }

    public function test_enums_are_castable_to_array()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => StringStatus::pending,
            'integer_status' => IntegerStatus::pending,
        ]);

        $this->assertEquals([
            'string_status' => 'pending',
            'integer_status' => 1,
        ], $model->toArray());
    }

    public function test_enums_are_castable_to_array_when_null()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => null,
            'integer_status' => null,
        ]);

        $this->assertEquals([
            'string_status' => null,
            'integer_status' => null,
        ], $model->toArray());
    }

    public function test_enums_are_converted_on_save()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => StringStatus::pending,
            'integer_status' => IntegerStatus::pending,
        ]);

        $model->save();

        $this->assertEquals((object) [
            'id' => $model->id,
            'string_status' => 'pending',
            'integer_status' => 1,
        ], DB::table('enum_casts')->where('id', $model->id)->first());
    }

    public function test_enums_accept_null_on_save()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => null,
            'integer_status' => null,
        ]);

        $model->save();

        $this->assertEquals((object) [
            'id' => $model->id,
            'string_status' => null,
            'integer_status' => null,
        ], DB::table('enum_casts')->where('id', $model->id)->first());
    }

    public function test_enums_accept_backed_value_on_save()
    {
        $model = new EloquentModelEnumCastingTestModel([
            'string_status' => 'pending',
            'integer_status' => 1,
        ]);

        $model->save();

        $model = EloquentModelEnumCastingTestModel::first();

        $this->assertEquals(StringStatus::pending, $model->string_status);
        $this->assertEquals(IntegerStatus::pending, $model->integer_status);
    }

    public function test_first_or_new()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'integer_status' => 1,
        ]);

        $model = EloquentModelEnumCastingTestModel::firstOrNew([
            'string_status' => StringStatus::pending,
        ]);

        $model2 = EloquentModelEnumCastingTestModel::firstOrNew([
            'string_status' => StringStatus::done,
        ]);

        $this->assertTrue($model->exists);
        $this->assertFalse($model2->exists);

        $model2->save();

        $this->assertEquals(StringStatus::done, $model2->string_status);
    }

    public function test_first_or_create()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'integer_status' => 1,
        ]);

        $model = EloquentModelEnumCastingTestModel::firstOrCreate([
            'string_status' => StringStatus::pending,
        ]);

        $model2 = EloquentModelEnumCastingTestModel::firstOrCreate([
            'string_status' => StringStatus::done,
        ]);

        $this->assertEquals(StringStatus::pending, $model->string_status);
        $this->assertEquals(StringStatus::done, $model2->string_status);
    }
}

class EloquentModelEnumCastingTestModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'enum_casts';

    public $casts = [
        'string_status' => StringStatus::class,
        'integer_status' => IntegerStatus::class,
    ];
}
