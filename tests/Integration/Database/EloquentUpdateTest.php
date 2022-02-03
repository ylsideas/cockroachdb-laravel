<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentUpdateTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('job')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('test_model3', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('counter');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function testBasicUpdate()
    {
        TestUpdateModel1::create([
            'name' => Str::random(),
            'title' => 'Ms.',
        ]);

        TestUpdateModel1::where('title', 'Ms.')->delete();

        $this->assertCount(0, TestUpdateModel1::all());
    }

    /** @group SkipMSSQL */
    public function testUpdateWithLimitsAndOrders()
    {
        for ($i = 1; $i <= 10; $i++) {
            TestUpdateModel1::create(['id' => $i]);
        }

        TestUpdateModel1::latest('id')->limit(3)->update(['title' => 'Dr.']);

        $this->assertSame('Dr.', TestUpdateModel1::find(8)->title);
        $this->assertNotSame('Dr.', TestUpdateModel1::find(7)->title);
    }

    public function testUpdatedAtWithJoins()
    {
        TestUpdateModel1::create([
            'id' => 1,
            'name' => 'Abdul',
            'title' => 'Mr.',
        ]);

        TestUpdateModel2::create([
            'id' => 1,
            'name' => Str::random(),
        ]);

        TestUpdateModel2::join('test_model1', function ($join) {
            $join->on('test_model1.id', '=', 'test_model2.id')
                ->where('test_model1.title', '=', 'Mr.');
        })->update(['test_model2.name' => 'Abdul', 'job' => 'Engineer']);

        $record = TestUpdateModel2::find(1);

        $this->assertSame('Engineer: Abdul', $record->job.': '.$record->name);
    }

    public function testSoftDeleteWithJoins()
    {
        TestUpdateModel1::create([
            'name' => Str::random(),
            'title' => 'Mr.',
            'id' => 1,
        ]);

        TestUpdateModel2::create([
            'id' => 1,
            'name' => Str::random(),
        ]);

        TestUpdateModel2::join('test_model1', function ($join) {
            $join->on('test_model1.id', '=', 'test_model2.id')
                ->where('test_model1.title', '=', 'Mr.');
        })->delete();

        $this->assertCount(0, TestUpdateModel2::all());
    }

    public function testIncrement()
    {
        TestUpdateModel3::create([
            'counter' => 0,
        ]);

        TestUpdateModel3::create([
            'counter' => 0,
        ])->delete();

        TestUpdateModel3::increment('counter');

        $models = TestUpdateModel3::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertEquals(1, $models[0]->counter);
        $this->assertEquals(0, $models[1]->counter);
    }
}

class TestUpdateModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
}

class TestUpdateModel2 extends Model
{
    use SoftDeletes;

    public $table = 'test_model2';
    protected $guarded = [];
}

class TestUpdateModel3 extends Model
{
    use SoftDeletes;

    public $table = 'test_model3';
    protected $fillable = ['counter'];
    protected $casts = ['deleted_at' => 'datetime'];
}